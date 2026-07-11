<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hits /dashboard over real HTTP rather than testing DashboardController's
 * logic in isolation - a PHP fatal parse error in the controller (a stray
 * blank line before <?php) went undetected for a while because every other
 * test exercised Livewire components directly and never actually dispatched
 * this route.
 */
class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_registrar_is_redirected_to_their_dashboard(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get('/dashboard')
            ->assertRedirect(route('registrar.dashboard'));
    }

    public function test_administrator_is_redirected_to_their_dashboard(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_dark_mode_toggle_renders_on_the_shared_layout(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('darkMode')
            ->assertSee('Toggle dark mode');
    }
}
