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
}
