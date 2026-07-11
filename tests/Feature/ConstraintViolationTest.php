<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
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
 * Phase 12: constraint tests that deliberately try to violate DB-level
 * rules and assert they fail - these exist alongside the higher-level
 * service tests (AcademicStructureTest, TeacherStudentFlowTest, etc.)
 * which only ever exercise the rules through the app's own code paths.
 * These instead hit Eloquent directly, bypassing every service, to prove
 * the constraint itself - not just the service that happens to respect
 * it - is what actually enforces the rule.
 */
class ConstraintViolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_academic_year_can_be_active_at_the_database_level(): void
    {
        AcademicYear::create(['name' => 'Year One', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_active' => true]);

        $this->expectException(QueryException::class);

        AcademicYear::create(['name' => 'Year Two', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
    }

    public function test_only_one_term_can_be_active_at_the_database_level(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);

        $this->expectException(QueryException::class);

        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'start_date' => '2027-01-05', 'end_date' => '2027-04-10', 'is_active' => true]);
    }

    public function test_a_student_cannot_have_two_attendance_records_on_the_same_day(): void
    {
        [$class, $student, $teacher] = $this->makeClassStudentTeacher();

        AttendanceRecord::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-09-10',
            'status' => AttendanceStatus::Present, 'marked_by' => $teacher->id, 'marked_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        AttendanceRecord::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-09-10',
            'status' => AttendanceStatus::Absent, 'marked_by' => $teacher->id, 'marked_at' => now(),
        ]);
    }

    public function test_a_student_cannot_have_two_result_entries_for_the_same_subject_and_term(): void
    {
        [$class, $student, $teacher] = $this->makeClassStudentTeacher();
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);

        ResultEntry::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
            'term_id' => $term->id, 'entered_by' => $teacher->id, 'score' => 80, 'max_score' => 100,
            'status' => ResultStatus::Draft,
        ]);

        $this->expectException(QueryException::class);

        ResultEntry::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
            'term_id' => $term->id, 'entered_by' => $teacher->id, 'score' => 90, 'max_score' => 100,
            'status' => ResultStatus::Draft,
        ]);
    }

    /**
     * @return array{0: SchoolClass, 1: Student, 2: Teacher}
     */
    private function makeClassStudentTeacher(): array
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_active' => false]);
        $class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01', 'current_class_id' => $class->id,
        ]);

        $teacherUser = User::create(['name' => 'Test Teacher', 'email' => 'teacher@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        return [$class, $student, $teacher];
    }
}
