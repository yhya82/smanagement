<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Livewire\Admin\GradeReviewIndex;
use App\Livewire\Admin\Teachers\Index as TeachersIndex;
use App\Livewire\Admin\Teachers\Show as TeachersShow;
use App\Livewire\Shared\Notifications as SharedNotifications;
use App\Livewire\Student\Attendance as StudentAttendance;
use App\Livewire\Student\Dashboard as StudentDashboard;
use App\Livewire\Student\Results as StudentResults;
use App\Livewire\Teacher\Attendance as TeacherAttendance;
use App\Livewire\Teacher\Dashboard as TeacherDashboard;
use App\Livewire\Teacher\Grades as TeacherGrades;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherStudentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolClass $class;

    private Subject $subject;

    private Term $term;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        $this->student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'current_class_id' => $this->class->id,
        ]);
    }

    public function test_full_teacher_and_student_flow(): void
    {
        // --- Admin onboards a teacher ---
        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->set('name', 'Jane Teacher')
            ->set('email', 'jane@school.test')
            ->set('hire_date', '2020-01-01')
            ->call('create')
            ->assertHasNoErrors();

        $teacher = Teacher::whereHas('user', fn ($q) => $q->where('email', 'jane@school.test'))->firstOrFail();
        $this->assertSame('pending', $teacher->status->value);
        $this->assertSame('active', $teacher->user->status->value, 'teacher account must be able to log in immediately');
        $this->assertTrue($teacher->user->hasRole('Teacher'), 'without this, the account exists but every route/dashboard redirect treats it as roleless');

        // --- Admin assigns the teacher to a subject/class ---
        Livewire::actingAs($this->admin)
            ->test(TeachersShow::class, ['teacher' => $teacher])
            ->set('subject_id', (string) $this->subject->id)
            ->set('class_id', (string) $this->class->id)
            ->set('term_id', (string) $this->term->id)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame('active', $teacher->fresh()->status->value, 'first assignment activates the teacher');
        $this->assertTrue($teacher->user->notifications()->where('type', 'subject_assigned')->exists());

        // --- Teacher dashboard shows the assignment ---
        Livewire::actingAs($teacher->user)
            ->test(TeacherDashboard::class)
            ->assertSee('Mathematics')
            ->assertSee('Blue Stream');

        // --- Teacher marks attendance ---
        Livewire::actingAs($teacher->user)
            ->test(TeacherAttendance::class, ['class' => $this->class])
            ->set("statuses.{$this->student->id}", AttendanceStatus::Present->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($this->student->attendanceRecords()->where('status', AttendanceStatus::Present)->exists());

        // --- Teacher enters and submits a grade ---
        $gradesComponent = Livewire::actingAs($teacher->user)
            ->test(TeacherGrades::class, ['class' => $this->class, 'subject' => $this->subject]);

        $gradesComponent->set("entries.{$this->student->id}.final.score", '88')
            ->set("entries.{$this->student->id}.final.max_score", '100')
            ->call('saveDrafts');

        $this->assertTrue($this->student->resultEntries()->where('status', 'draft')->exists());

        $gradesComponent->call('submitFinal');
        $this->assertTrue($this->student->resultEntries()->where('status', 'submitted')->exists());

        // --- Admin reviews and approves the grade ---
        Livewire::actingAs($this->admin)
            ->test(GradeReviewIndex::class)
            ->assertSee('Test Student')
            ->assertSee('88')
            ->call('approve', $this->student->resultEntries()->first()->id);

        $this->assertTrue($this->student->resultEntries()->where('status', 'approved')->exists());
        $this->assertTrue($this->student->user->notifications()->where('type', 'result_approved')->exists());

        // --- Student sees the approved result, attendance, and notification ---
        Livewire::actingAs($this->student->user)
            ->test(StudentResults::class)
            ->assertSee('Mathematics')
            ->assertSee('88');

        Livewire::actingAs($this->student->user)
            ->test(StudentAttendance::class)
            ->assertSee('Present');

        Livewire::actingAs($this->student->user)
            ->test(StudentDashboard::class)
            ->assertSee('Test');

        $notification = $this->student->user->notifications()->where('type', 'result_approved')->firstOrFail();

        Livewire::actingAs($this->student->user)
            ->test(SharedNotifications::class)
            ->assertSee('Result approved')
            ->call('markRead', $notification->id);

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_teacher_cannot_mark_attendance_for_an_unassigned_class(): void
    {
        $teacherUser = User::create(['name' => 'Other Teacher', 'email' => 'other@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T2', 'hire_date' => '2020-01-01']);

        $this->actingAs($teacherUser)
            ->get(route('teacher.attendance', $this->class))
            ->assertForbidden();
    }

    public function test_a_teacher_cannot_grade_a_subject_they_are_not_assigned_to(): void
    {
        $teacherUser = User::create(['name' => 'Other Teacher', 'email' => 'other2@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T3', 'hire_date' => '2020-01-01']);

        $this->actingAs($teacherUser)
            ->get(route('teacher.grades', [$this->class, $this->subject]))
            ->assertForbidden();
    }
}
