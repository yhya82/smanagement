<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Livewire\Teacher\Attendance as TeacherAttendance;
use App\Livewire\Teacher\Grades as TeacherGrades;
use App\Livewire\Teacher\Remarks as TeacherRemarks;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\GradeLevel;
use App\Models\ResultEntry;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A Livewire snapshot's checksum protects existing property VALUES from
 * tampering, but not which array KEYS a client can add to a public array
 * property before submitting - these lock down the server-side re-check
 * that stops a teacher's own class-scoped form from being used to write
 * records for a student in a class they have no access to.
 */
class TeacherSubmissionScopeTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;

    private SchoolClass $ownClass;

    private SchoolClass $foreignClass;

    private Student $foreignStudent;

    private Term $term;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $this->ownClass = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Own Class']);
        $this->foreignClass = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Foreign Class']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        $this->teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'class_id' => $this->ownClass->id, 'term_id' => $this->term->id, 'is_active' => true,
        ]);

        $foreignStudentUser = User::factory()->create(['status' => UserStatus::Active]);
        $this->foreignStudent = Student::create([
            'user_id' => $foreignStudentUser->id, 'student_no' => 'S-foreign', 'first_name' => 'Foreign', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $this->foreignClass->id,
        ]);
    }

    public function test_attendance_ignores_a_tampered_student_id_outside_the_class(): void
    {
        Livewire::actingAs($this->teacher->user)
            ->test(TeacherAttendance::class, ['class' => $this->ownClass])
            ->set("statuses.{$this->foreignStudent->id}", 'absent')
            ->call('save');

        $this->assertSame(0, AttendanceRecord::where('student_id', $this->foreignStudent->id)->count());
    }

    public function test_grades_ignores_a_tampered_student_id_outside_the_class(): void
    {
        Livewire::actingAs($this->teacher->user)
            ->test(TeacherGrades::class, ['class' => $this->ownClass, 'subject' => $this->subject])
            ->set("entries.{$this->foreignStudent->id}.midterm.score", '30')
            ->set("entries.{$this->foreignStudent->id}.midterm.max_score", '40')
            ->call('saveDrafts');

        $this->assertSame(0, ResultEntry::where('student_id', $this->foreignStudent->id)->count());
    }

    public function test_remarks_ignores_a_tampered_student_id_outside_the_class(): void
    {
        $this->ownClass->update(['homeroom_teacher_id' => $this->teacher->id]);

        Livewire::actingAs($this->teacher->user)
            ->test(TeacherRemarks::class, ['class' => $this->ownClass])
            ->set('termId', (string) $this->term->id)
            ->set("remarks.{$this->foreignStudent->id}", 'Should never be written.')
            ->call('save');

        $this->assertSame(0, TermRanking::where('student_id', $this->foreignStudent->id)->count());
    }
}
