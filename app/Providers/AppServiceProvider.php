<?php

namespace App\Providers;

use App\Models\SecurityEvent;
use App\Models\StudentHealthRecord;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Notifications\AccountLockedOut;
use App\Observers\StudentHealthRecordObserver;
use App\Observers\TeacherActivationObserver;
use App\Observers\UserObserver;
use App\Support\SafeNotifier;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Administrator has no data-access *scope* restriction anywhere in
        // the SRS, so view/viewAny/create/approve are bypassed unconditionally
        // rather than threading an "is admin" check through every policy.
        // update/delete are deliberately excluded: several policies hardcode
        // invariants on those two abilities that must hold even for
        // Administrator - the 7-day attendance edit lock, and "never
        // hard-delete" on applications/results/promotions/audit logs per
        // SRS §22. Excluding them here means those cases fall through to
        // the real policy method instead of being waved through.
        Gate::before(function (User $user, string $ability) {
            if (! $user->hasRole('Administrator')) {
                return null;
            }

            return in_array($ability, ['update', 'delete'], true) ? null : true;
        });

        TeacherSubjectAssignment::observe(TeacherActivationObserver::class);
        StudentHealthRecord::observe(StudentHealthRecordObserver::class);
        User::observe(UserObserver::class);

        $this->registerSecurityEventLogging();
        $this->registerHealthChecks();
        $this->registerSlowQueryLogging();
    }

    /**
     * Nothing in this app previously surfaced "which query/page is actually
     * slow" - the only way to find out was a support ticket and a guess.
     * A 200ms threshold is generous enough to stay quiet under normal
     * load while still catching the N+1-shaped regressions this app has
     * had before (see the Timetable/CalendarEvent fixes).
     */
    private function registerSlowQueryLogging(): void
    {
        DB::listen(function ($query): void {
            if ($query->time > 200) {
                Log::warning('Slow query', [
                    'sql' => $query->sql,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                    'url' => request()?->fullUrl(),
                ]);
            }
        });
    }

    /**
     * /up (Laravel's default health route) otherwise only proves the app
     * booted and dispatched an event - it says nothing about whether a
     * queue worker is actually running, which the README identifies as the
     * one failure mode nothing else detects automatically. A growing
     * `jobs` table is the cheapest available signal that no worker is
     * draining it; failed_jobs piling up is a second, independent signal
     * something is wrong even if a worker is technically alive.
     */
    private function registerHealthChecks(): void
    {
        Event::listen(DiagnosingHealth::class, function (): void {
            try {
                DB::connection()->getPdo();
            } catch (\Throwable $e) {
                throw new RuntimeException('Database unreachable: '.$e->getMessage());
            }

            $pendingJobs = DB::table('jobs')->count();

            if ($pendingJobs > 500) {
                throw new RuntimeException("Queue backlog is unusually large ({$pendingJobs} pending jobs) - a worker may not be running.");
            }

            $failedJobs = DB::table('failed_jobs')->count();

            if ($failedJobs > 100) {
                throw new RuntimeException("An unusually high number of jobs have failed ({$failedJobs}) - the queue needs attention.");
            }
        });
    }

    /**
     * Failed logins and lockouts previously left no trail anywhere - not in
     * the logs, not in the app. Cross-referencing by email (rather than
     * relying on Failed::$user, which Fortify always passes as null) lets
     * an admin see "which account is being attacked," not just "a login
     * failed somewhere."
     *
     * Lockout detection deliberately does NOT rely on
     * Illuminate\Auth\Events\Lockout: the login route already carries its
     * own `throttle:login` route middleware (see FortifyServiceProvider's
     * 'login' RateLimiter), which intercepts and returns 429 *before*
     * Fortify's internal pipeline - including its own Lockout-firing step -
     * ever runs. Confirmed by testing directly: 6 rapid failed attempts
     * produce a 429 on the 6th with zero Lockout events observed. A
     * self-contained counter on the reliably-firing Failed event avoids
     * depending on an inner pipeline stage this app's own routing never
     * actually reaches. The Lockout listener is kept anyway as a harmless
     * backstop in case that routing ever changes.
     */
    private function registerSecurityEventLogging(): void
    {
        Event::listen(Failed::class, function (Failed $event): void {
            $email = $event->credentials[Fortify::username()] ?? null;
            $user = $email ? User::where('email', $email)->first() : null;

            Log::warning('Failed login attempt', ['email' => $email, 'ip' => request()?->ip()]);

            SecurityEvent::log('login_failed', [
                'user_id' => $user?->id,
                'email' => $email,
            ]);

            if (! $email) {
                return;
            }

            $lockoutKey = 'login-lockout:'.Str::lower($email).'|'.request()?->ip();

            RateLimiter::hit($lockoutKey, 60);

            if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
                $this->recordAccountLockout($email, $user);
            }
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            $email = $event->request->input(Fortify::username());

            $this->recordAccountLockout($email, $email ? User::where('email', $email)->first() : null);
        });
    }

    private function recordAccountLockout(?string $email, ?User $user): void
    {
        Log::warning('Account locked out after repeated failed logins', ['email' => $email, 'ip' => request()?->ip()]);

        SecurityEvent::log('account_lockout', [
            'user_id' => $user?->id,
            'email' => $email,
        ]);

        $administrators = User::whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))->get();

        foreach ($administrators as $administrator) {
            SafeNotifier::send($administrator, new AccountLockedOut((string) $email));
        }
    }
}
