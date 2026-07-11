<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Roles\Show as RolesShow;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\Show as UsersShow;
use App\Livewire\Shared\ChangePassword;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
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

    // --- Forced password change ---

    public function test_a_user_who_must_change_their_password_is_redirected_there(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => true]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_a_user_who_has_already_changed_their_password_is_not_redirected(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => false]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('registrar.dashboard'));
    }

    public function test_updating_password_clears_the_must_change_password_flag(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => true, 'password' => bcrypt('temp-pass')]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->set('current_password', 'temp-pass')
            ->set('password', 'NewPassw0rd!')
            ->set('password_confirmation', 'NewPassw0rd!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    /**
     * Regression test for a real bug: Livewire's own AJAX update endpoint
     * runs through the 'web' middleware group too, so the change-password
     * form's own submission was getting redirected by the forced-change
     * middleware before updatePassword() ever ran - the password appeared
     * to "not work" because it was never actually being saved. Livewire::test()
     * doesn't dispatch through the HTTP kernel/middleware at all, which is
     * exactly why the earlier Livewire-only test didn't catch this.
     */
    public function test_livewire_update_requests_are_not_blocked_by_the_forced_password_change_redirect(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => true]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        $response = $this->actingAs($user)->post(route('default-livewire.update'), []);

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_updating_password_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'password' => bcrypt('temp-pass')]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'NewPassw0rd!')
            ->set('password_confirmation', 'NewPassw0rd!')
            ->call('updatePassword')
            ->assertHasErrors('current_password');
    }

    // --- Admin user management ---

    public function test_admin_can_create_a_registrar_user_with_a_temporary_password(): void
    {
        $registrarRole = Role::where('name', 'Registrar')->first();

        Livewire::actingAs($this->admin)
            ->test(UsersCreate::class)
            ->set('name', 'New Registrar')
            ->set('email', 'newreg@test.com')
            ->set('role_id', (string) $registrarRole->id)
            ->call('create')
            ->assertSet('temporaryPassword', fn ($value) => ! empty($value));

        $user = User::where('email', 'newreg@test.com')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('Registrar'));
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->call('toggleStatus');

        $this->assertSame('inactive', $user->fresh()->status->value);

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->call('toggleStatus');

        $this->assertSame('active', $user->fresh()->status->value);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $this->admin])
            ->call('toggleStatus')
            ->assertSet('statusError', fn ($value) => str_contains($value, 'cannot deactivate your own account'));

        $this->assertSame('active', $this->admin->fresh()->status->value);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => false]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->call('resetPassword')
            ->assertSet('temporaryPassword', fn ($value) => ! empty($value));

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_resetting_a_password_invalidates_the_users_existing_sessions(): void
    {
        // invalidateOtherSessions() only acts when it believes the app is
        // actually running on the database session driver - the test env
        // defaults to 'array' for speed, so this is set explicitly to
        // exercise the real guarded code path rather than its no-op branch.
        config(['session.driver' => 'database']);

        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => false]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        DB::table('sessions')->insert([
            'id' => 'stale-session-id', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => base64_encode('x'), 'last_activity' => time(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->call('resetPassword');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_deactivating_a_user_invalidates_their_existing_sessions(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        DB::table('sessions')->insert([
            'id' => 'stale-session-id-2', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => base64_encode('x'), 'last_activity' => time(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->call('toggleStatus');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_changing_your_own_password_invalidates_other_sessions_but_keeps_the_current_one(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create(['status' => UserStatus::Active, 'must_change_password' => false, 'password' => Hash::make('OldPass123!')]);

        DB::table('sessions')->insert([
            'id' => 'other-device-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => base64_encode('x'), 'last_activity' => time(),
        ]);

        $component = Livewire::actingAs($user)->test(ChangePassword::class);
        $currentSessionId = session()->getId();

        DB::table('sessions')->insert([
            'id' => $currentSessionId, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => base64_encode('x'), 'last_activity' => time(),
        ]);

        $component->set('current_password', 'OldPass123!')
            ->set('password', 'NewPass123!')
            ->set('password_confirmation', 'NewPass123!')
            ->call('updatePassword');

        $this->assertSame(0, DB::table('sessions')->where('id', 'other-device-session')->count());
        $this->assertSame(1, DB::table('sessions')->where('id', $currentSessionId)->count());
    }

    public function test_invalidate_other_sessions_is_a_safe_no_op_when_not_on_the_database_session_driver(): void
    {
        config(['session.driver' => 'array']);

        $user = User::factory()->create(['status' => UserStatus::Active]);

        DB::table('sessions')->insert([
            'id' => 'stale-session-id-3', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => base64_encode('x'), 'last_activity' => time(),
        ]);

        // Must not throw, and must leave the row alone rather than pretend
        // to have invalidated a session it has no way to actually reach.
        $user->invalidateOtherSessions();

        $this->assertSame(1, DB::table('sessions')->where('id', 'stale-session-id-3')->count());
    }

    public function test_registrar_cannot_access_user_management(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    // --- Role/permission management ---

    public function test_admin_can_create_a_custom_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolesIndex::class)
            ->set('name', 'Librarian')
            ->set('description', 'Manages the library')
            ->call('create')
            ->assertHasNoErrors();

        $role = Role::where('name', 'Librarian')->firstOrFail();
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->is_active);
    }

    public function test_admin_can_change_permissions_on_a_role(): void
    {
        $role = Role::create(['name' => 'Librarian', 'is_system' => false, 'is_active' => true]);
        $permission = Permission::first();

        Livewire::actingAs($this->admin)
            ->test(RolesShow::class, ['role' => $role])
            ->set('selectedPermissionIds', [$permission->id])
            ->call('savePermissions');

        $this->assertTrue($role->permissions()->where('permissions.id', $permission->id)->exists());
    }

    public function test_saving_permissions_ignores_ids_that_do_not_exist(): void
    {
        $role = Role::create(['name' => 'Librarian', 'is_system' => false, 'is_active' => true]);
        $permission = Permission::first();
        $bogusId = Permission::max('id') + 999;

        Livewire::actingAs($this->admin)
            ->test(RolesShow::class, ['role' => $role])
            ->set('selectedPermissionIds', [$permission->id, $bogusId])
            ->call('savePermissions');

        $this->assertSame([$permission->id], $role->permissions()->pluck('permissions.id')->all());
    }

    public function test_the_administrator_roles_permissions_cannot_be_edited(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $originalPermissionIds = $adminRole->permissions()->pluck('permissions.id')->all();
        $onePermission = Permission::first();

        Livewire::actingAs($this->admin)
            ->test(RolesShow::class, ['role' => $adminRole])
            ->set('selectedPermissionIds', [$onePermission->id])
            ->call('savePermissions')
            ->assertSet('permissionsError', fn ($value) => str_contains($value, "can't be edited"));

        $this->assertSame($originalPermissionIds, $adminRole->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_disabling_a_role_revokes_it_from_holders_immediately(): void
    {
        $role = Role::create(['name' => 'Librarian', 'is_system' => false, 'is_active' => true]);
        $role->permissions()->attach(Permission::first());

        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('Librarian'));

        Livewire::actingAs($this->admin)
            ->test(RolesIndex::class)
            ->call('toggleActive', $role->id);

        $this->assertFalse($user->hasRole('Librarian'));
    }

    public function test_system_roles_cannot_be_disabled(): void
    {
        $teacherRole = Role::where('name', 'Teacher')->first();

        Livewire::actingAs($this->admin)
            ->test(RolesIndex::class)
            ->call('toggleActive', $teacherRole->id);

        $this->assertTrue($teacherRole->fresh()->is_active);
    }

    public function test_system_role_name_cannot_be_changed(): void
    {
        $teacherRole = Role::where('name', 'Teacher')->first();

        Livewire::actingAs($this->admin)
            ->test(RolesShow::class, ['role' => $teacherRole])
            ->set('name', 'Not Teacher')
            ->set('description', 'Updated description')
            ->call('updateDetails');

        $teacherRole->refresh();
        $this->assertSame('Teacher', $teacherRole->name);
        $this->assertSame('Updated description', $teacherRole->description);
    }

    public function test_registrar_cannot_access_role_management(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    // --- Custom role assignment (previously a dead end: creatable but unassignable) ---

    public function test_a_custom_role_can_be_assigned_to_a_new_user(): void
    {
        $librarianRole = Role::create(['name' => 'Librarian', 'is_system' => false, 'is_active' => true]);

        Livewire::actingAs($this->admin)
            ->test(UsersCreate::class)
            ->set('name', 'New Librarian')
            ->set('email', 'librarian@test.com')
            ->set('role_id', (string) $librarianRole->id)
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'librarian@test.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Librarian'));
    }

    public function test_teacher_and_student_roles_are_not_offered_when_creating_a_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersCreate::class)
            ->assertDontSee('Teacher')
            ->assertDontSee('Student');
    }

    public function test_a_malicious_role_id_cannot_create_a_user_holding_teacher_role(): void
    {
        $teacherRole = Role::where('name', 'Teacher')->first();

        Livewire::actingAs($this->admin)
            ->test(UsersCreate::class)
            ->set('name', 'Sneaky User')
            ->set('email', 'sneaky@test.com')
            ->set('role_id', (string) $teacherRole->id)
            ->call('create')
            ->assertSet('roleError', fn ($value) => str_contains($value, 'cannot be assigned'));

        $this->assertNull(User::where('email', 'sneaky@test.com')->first());
    }

    public function test_admin_can_reassign_a_users_role_to_a_custom_role(): void
    {
        $librarianRole = Role::create(['name' => 'Librarian', 'is_system' => false, 'is_active' => true]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->set('role_id', (string) $librarianRole->id)
            ->call('updateRole')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('Librarian'));
        $this->assertFalse($user->hasRole('Registrar'));
    }

    public function test_a_users_teacher_role_cannot_be_reassigned_through_user_management(): void
    {
        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        \App\Models\Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $registrarRole = Role::where('name', 'Registrar')->first();

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $teacherUser])
            ->assertSet('canReassignRole', false)
            ->set('role_id', (string) $registrarRole->id)
            ->call('updateRole')
            ->assertSet('roleError', fn ($value) => str_contains($value, 'Teacher or Student'));

        $this->assertTrue($teacherUser->fresh()->hasRole('Teacher'));
    }

    public function test_a_malicious_role_id_cannot_assign_teacher_role_through_user_management(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach(Role::where('name', 'Registrar')->first());
        $teacherRole = Role::where('name', 'Teacher')->first();

        Livewire::actingAs($this->admin)
            ->test(UsersShow::class, ['user' => $user])
            ->set('role_id', (string) $teacherRole->id)
            ->call('updateRole')
            ->assertSet('roleError', fn ($value) => str_contains($value, 'cannot be assigned'));

        $this->assertFalse($user->fresh()->hasRole('Teacher'));
        $this->assertTrue($user->fresh()->hasRole('Registrar'));
    }
}
