<?php

namespace App\Providers;

use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Observers\TeacherActivationObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    }
}
