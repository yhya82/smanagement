<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * One placeholder class per grade level so admission approval has
     * somewhere to assign a student - full class-management UI (multiple
     * streams per grade, capacity, etc.) is a later Admin slice, not this
     * one. An administrator can add more classes once that exists.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (! $academicYear) {
            return;
        }

        foreach (GradeLevel::all() as $gradeLevel) {
            SchoolClass::firstOrCreate([
                'grade_level_id' => $gradeLevel->id,
                'academic_year_id' => $academicYear->id,
                'name' => $gradeLevel->name,
            ]);
        }
    }
}
