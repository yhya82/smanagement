<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\Settings\Edit as SettingsEdit;
use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
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

    public function test_current_creates_a_default_row_when_none_exists(): void
    {
        $this->assertSame(0, SchoolSetting::count());

        $setting = SchoolSetting::current();

        $this->assertSame(1, SchoolSetting::count());
        $this->assertNotEmpty($setting->name);
    }

    public function test_admin_can_update_school_settings_and_upload_a_logo(): void
    {
        Storage::fake('school-logo');

        Livewire::actingAs($this->admin)
            ->test(SettingsEdit::class)
            ->set('name', 'Sunrise Academy')
            ->set('address', '123 Main St')
            ->set('city', 'Accra')
            ->set('website', 'https://sunrise.example.com')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true);

        $setting = SchoolSetting::current();
        $this->assertSame('Sunrise Academy', $setting->name);
        $this->assertSame('Accra', $setting->city);
        $this->assertNotNull($setting->logo_path);
        Storage::disk('school-logo')->assertExists($setting->logo_path);
    }

    public function test_website_must_be_a_valid_url(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsEdit::class)
            ->set('name', 'Sunrise Academy')
            ->set('website', 'not-a-url')
            ->call('save')
            ->assertHasErrors('website');
    }

    public function test_registrar_cannot_access_settings(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_school_name_appears_on_the_login_page(): void
    {
        SchoolSetting::current()->update(['name' => 'Sunrise Academy']);

        $this->get(route('login'))->assertSee('Sunrise Academy');
    }

    public function test_school_name_appears_in_the_sidebar(): void
    {
        SchoolSetting::current()->update(['name' => 'Sunrise Academy']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertSee('Sunrise Academy');
    }
}
