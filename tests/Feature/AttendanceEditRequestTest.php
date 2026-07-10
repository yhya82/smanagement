<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Livewire\Admin\AttendanceEditRequests\Index as AttendanceEditRequestsIndex;
use App\Livewire\Teacher\Attendance as TeacherAttendance;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceEditRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacherUser;

    private Teacher $teacher;

    private SchoolClass $class;

    private Student $student;

    private AttendanceRecord $lockedRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $this->teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $this->teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $subject->id, 'class_id' => $this->class->id,
            'term_id' => $term->id, 'is_active' => true,
        ]);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        $this->student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'current_class_id' => $this->class->id,
        ]);

        $this->lockedRecord = AttendanceRecord::create([
            'student_id' => $this->student->id, 'class_id' => $this->class->id, 'date' => now()->subDays(10)->toDateString(),
            'status' => AttendanceStatus::Absent, 'marked_by' => $this->teacher->id, 'marked_at' => now()->subDays(10),
            'locked_at' => now()->subDays(3),
        ]);
    }

    public function test_teacher_can_request_an_edit_for_a_locked_record(): void
    {
        Livewire::actingAs($this->teacherUser)
            ->test(TeacherAttendance::class, ['class' => $this->class])
            ->set('date', $this->lockedRecord->date->toDateString())
            ->call('openEditRequest', $this->student->id)
            ->set('requestedStatus', 'present')
            ->set('reason', 'Marked absent by mistake')
            ->call('submitEditRequest')
            ->assertHasNoErrors();

        $this->assertTrue($this->lockedRecord->editRequests()->where('status', 'pending')->exists());
    }

    public function test_teacher_cannot_request_an_edit_for_a_record_still_within_the_window(): void
    {
        $record = AttendanceRecord::create([
            'student_id' => $this->student->id, 'class_id' => $this->class->id, 'date' => now()->toDateString(),
            'status' => AttendanceStatus::Present, 'marked_by' => $this->teacher->id, 'marked_at' => now(),
        ]);

        Livewire::actingAs($this->teacherUser)
            ->test(TeacherAttendance::class, ['class' => $this->class])
            ->set('date', $record->date->toDateString())
            ->call('openEditRequest', $this->student->id)
            ->set('requestedStatus', 'absent')
            ->set('reason', 'Testing')
            ->call('submitEditRequest')
            ->assertSet('editRequestError', fn ($value) => str_contains($value, '7-day'));

        $this->assertSame(0, $record->editRequests()->count());
    }

    public function test_admin_can_approve_an_edit_request(): void
    {
        $request = app(\App\Services\AttendanceService::class)->requestEdit(
            $this->lockedRecord, $this->teacherUser, AttendanceStatus::Present, 'Marked absent by mistake'
        );

        Livewire::actingAs($this->admin)
            ->test(AttendanceEditRequestsIndex::class)
            ->assertSee('Test Student')
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertSame('approved', $request->fresh()->status->value);
        $this->assertSame('present', $this->lockedRecord->fresh()->status->value);
    }

    public function test_admin_can_reject_an_edit_request(): void
    {
        $request = app(\App\Services\AttendanceService::class)->requestEdit(
            $this->lockedRecord, $this->teacherUser, AttendanceStatus::Present, 'Marked absent by mistake'
        );

        Livewire::actingAs($this->admin)
            ->test(AttendanceEditRequestsIndex::class)
            ->call('reject', $request->id);

        $this->assertSame('rejected', $request->fresh()->status->value);
        $this->assertSame('absent', $this->lockedRecord->fresh()->status->value, 'rejected request must not change the record');
    }

    public function test_registrar_cannot_access_the_edit_request_queue(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.attendance-edit-requests.index'))
            ->assertForbidden();
    }

    public function test_teacher_cannot_approve_their_own_edit_request(): void
    {
        // The admin-only route is gated by role:Administrator middleware
        // (see test_registrar_cannot_access_the_edit_request_queue for that
        // boundary) - this checks the deeper policy boundary: a teacher who
        // holds attendance.edit (and so can view the queue per viewAny())
        // still can't call approve(), which requires attendance.edit.approve.
        $request = app(\App\Services\AttendanceService::class)->requestEdit(
            $this->lockedRecord, $this->teacherUser, AttendanceStatus::Present, 'Marked absent by mistake'
        );

        Livewire::actingAs($this->teacherUser)
            ->test(AttendanceEditRequestsIndex::class)
            ->call('approve', $request->id)
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status->value);
    }
}
