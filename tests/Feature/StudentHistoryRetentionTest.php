<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Status over hard delete" (quoted in ResultService's own docblock) was
 * only a convention followed by the UI, never actually enforced at the
 * schema level - deleting a student's user record used to cascade away
 * their entire academic history silently. These FKs are now restrictOnDelete()
 * instead, so that deletion fails loudly rather than succeeding silently.
 */
class StudentHistoryRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_students_user_account_is_blocked_while_history_exists(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $class->id,
        ]);

        ResultEntry::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
            'term_id' => $term->id, 'score' => 90, 'max_score' => 100, 'status' => ResultStatus::Approved,
            'entered_by' => $teacher->id,
        ]);

        $this->expectException(QueryException::class);

        $studentUser->delete();
    }
}
