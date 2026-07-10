<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6',
            'Secondary 1', 'Secondary 2', 'Secondary 3', 'Secondary 4', 'Secondary 5', 'Secondary 6',
        ];

        foreach ($levels as $index => $name) {
            GradeLevel::firstOrCreate(['name' => $name], ['sort_order' => $index + 1]);
        }
    }
}
