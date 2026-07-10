<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    /**
     * Ensures the single settings row always exists - the sidebar and login
     * page read it on every request, so there's no "not configured yet"
     * state to handle, just default text until an admin edits it.
     */
    public function run(): void
    {
        SchoolSetting::current();
    }
}
