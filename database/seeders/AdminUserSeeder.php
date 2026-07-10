<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Bootstraps the very first account. Without this, nobody could log in
     * to create any other user - SRS §4 says only administrators create
     * accounts, so the first one has to come from somewhere other than the
     * app itself. must_change_password forces a real password to be set on
     * first login instead of leaving this one in place.
     */
    public function run(): void
    {
        $email = 'admin@example.com';

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = 'ChangeMe123!';

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => $email,
            'password' => Hash::make($password),
            'status' => UserStatus::Active,
            'must_change_password' => true,
        ]);

        $adminRole = Role::where('name', 'Administrator')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command?->warn("Seeded admin account - email: {$email} / password: {$password} (must be changed on first login)");
    }
}
