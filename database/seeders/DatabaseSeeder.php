<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with the baseline data the app can't
     * function without: roles/permissions, admission document types, grade
     * levels, an initial academic year/term, and the bootstrap admin account.
     */
    public function run(): void
    {
        $this->call([
            SchoolSettingSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            DocumentTypeSeeder::class,
            GradeLevelSeeder::class,
            AcademicYearSeeder::class,
            ClassSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
