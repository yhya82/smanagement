<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Seeds one placeholder academic year with 3 terms so the app has an
     * active year/term on first boot (nothing else - enrollment, attendance,
     * grading - can attach to anything otherwise). An administrator can
     * create and activate a real academic year afterwards; this one is just
     * a working starting point, not meant to reflect an actual calendar.
     */
    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2025/2026'],
            ['start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_active' => true]
        );

        $terms = [
            ['name' => 'Term 1', 'start_date' => '2025-09-01', 'end_date' => '2025-12-12', 'is_active' => false],
            ['name' => 'Term 2', 'start_date' => '2026-01-05', 'end_date' => '2026-04-03', 'is_active' => false],
            ['name' => 'Term 3', 'start_date' => '2026-04-20', 'end_date' => '2026-07-31', 'is_active' => true],
        ];

        foreach ($terms as $term) {
            Term::firstOrCreate(
                ['academic_year_id' => $year->id, 'name' => $term['name']],
                $term
            );
        }
    }
}
