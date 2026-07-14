<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\SecurityEvents\Index as SecurityEventsIndex;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Previously nothing recorded a failed login, an account lockout, or a
 * permission denial anywhere - an admin reviewing "did someone try to break
 * into this account" had no way to answer that question. These three event
 * sources (Illuminate\Auth\Events\Failed/Lockout, and AuthorizationException
 * via bootstrap/app.php's render() hook) are registered in
 * AppServiceProvider::registerSecurityEventLogging() and bootstrap/app.php.
 */
class SecurityEventLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());
    }

    public function test_a_failed_login_attempt_is_recorded(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'password' => bcrypt('CorrectPass123!')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'WrongPassword']);

        $event = SecurityEvent::where('event', 'login_failed')->firstOrFail();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame($user->email, $event->email);
    }

    public function test_repeated_failed_logins_lock_out_and_notify_administrators(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'password' => bcrypt('CorrectPass123!')]);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'WrongPassword']);
        }

        $this->assertTrue(SecurityEvent::where('event', 'account_lockout')->where('email', $user->email)->exists());
        $this->assertTrue($this->admin->notifications()->where('type', 'account_locked_out')->exists());
    }

    public function test_a_permission_denial_is_recorded_without_changing_the_403_response(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $studentUser = User::create(['name' => 'S', 'email' => 'student-pd@test.com', 'password' => 'x', 'status' => UserStatus::Active]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'S', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => \App\Enums\Gender::Male, 'admission_date' => '2024-01-01',
            'status' => \App\Enums\StudentStatus::Active,
        ]);

        $this->actingAs($registrar)
            ->get(route('admin.students.show', $student))
            ->assertForbidden();

        $event = SecurityEvent::where('event', 'permission_denied')->firstOrFail();
        $this->assertSame($registrar->id, $event->user_id);
    }

    public function test_admin_can_view_and_filter_security_events(): void
    {
        SecurityEvent::log('login_failed', ['email' => 'attacker@test.com']);
        SecurityEvent::log('permission_denied', ['user_id' => $this->admin->id, 'context' => ['url' => 'https://example.test/admin/x']]);

        // "permission_denied" itself always appears (it's a <select> filter
        // option regardless of which one is active) - check a value that
        // only appears in a matching row instead, same reasoning as the
        // Audit Log test's own equivalent case.
        Livewire::actingAs($this->admin)
            ->test(SecurityEventsIndex::class)
            ->assertSee('attacker@test.com')
            ->assertSee('example.test/admin/x')
            ->set('event', 'login_failed')
            ->assertSee('attacker@test.com')
            ->assertDontSee('example.test/admin/x');
    }

    public function test_registrar_cannot_view_security_events(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.security-events.index'))
            ->assertForbidden();
    }
}
