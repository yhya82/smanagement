<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\Settings\Calendar as CalendarComponent;
use App\Livewire\Teacher\Attendance as TeacherAttendance;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\AttendanceService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SchoolCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Term $term;

    private SchoolClass $class;

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
    }

    public function test_admin_can_add_and_remove_a_calendar_event(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CalendarComponent::class)
            ->set('termId', (string) $this->term->id)
            ->set('date', '2026-12-25')
            ->set('title', 'Christmas Day')
            ->set('type', 'holiday')
            ->call('create')
            ->assertHasNoErrors();

        $event = CalendarEvent::where('title', 'Christmas Day')->firstOrFail();
        $this->assertTrue($event->type->value === 'holiday');

        $component->call('delete', $event->id);
        $this->assertSame(0, CalendarEvent::count());
    }

    public function test_is_holiday_helper_matches_only_holiday_type_events(): void
    {
        CalendarEvent::create(['term_id' => $this->term->id, 'date' => '2026-12-25', 'title' => 'Christmas Day', 'type' => 'holiday']);
        CalendarEvent::create(['term_id' => $this->term->id, 'date' => '2026-10-10', 'title' => 'Sports Day', 'type' => 'event']);

        $this->assertTrue(CalendarEvent::isHoliday('2026-12-25'));
        $this->assertFalse(CalendarEvent::isHoliday('2026-10-10'));
        $this->assertFalse(CalendarEvent::isHoliday('2026-10-11'));
    }

    public function test_attendance_service_refuses_to_mark_a_public_holiday(): void
    {
        CalendarEvent::create(['term_id' => $this->term->id, 'date' => '2026-12-25', 'title' => 'Christmas Day', 'type' => 'holiday']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        \App\Models\TeacherSubjectAssignment::create([
            'teacher_id' => $teacher->id, 'subject_id' => \App\Models\Subject::create(['name' => 'Mathematics', 'code' => 'MATH1'])->id,
            'class_id' => $this->class->id, 'term_id' => $this->term->id, 'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('public holiday');

        app(AttendanceService::class)->mark($this->class, '2026-12-25', [], $teacher);
    }

    public function test_teacher_attendance_screen_shows_a_holiday_banner_instead_of_the_form(): void
    {
        CalendarEvent::create(['term_id' => $this->term->id, 'date' => '2026-12-25', 'title' => 'Christmas Day', 'type' => 'holiday']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        \App\Models\TeacherSubjectAssignment::create([
            'teacher_id' => $teacher->id, 'subject_id' => \App\Models\Subject::create(['name' => 'Mathematics', 'code' => 'MATH1'])->id,
            'class_id' => $this->class->id, 'term_id' => $this->term->id, 'is_active' => true,
        ]);

        Livewire::actingAs($teacher->user)
            ->test(TeacherAttendance::class, ['class' => $this->class])
            ->set('date', '2026-12-25')
            ->assertSee('Public holiday: Christmas Day')
            ->assertDontSee('Save Attendance');
    }

    public function test_marking_a_non_holiday_date_still_works(): void
    {
        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2024-01-01',
            'current_class_id' => $this->class->id,
        ]);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        \App\Models\TeacherSubjectAssignment::create([
            'teacher_id' => $teacher->id, 'subject_id' => \App\Models\Subject::create(['name' => 'Mathematics', 'code' => 'MATH1'])->id,
            'class_id' => $this->class->id, 'term_id' => $this->term->id, 'is_active' => true,
        ]);

        $result = app(AttendanceService::class)->mark($this->class, '2026-09-10', [$student->id => AttendanceStatus::Present], $teacher);

        $this->assertSame([$student->id], $result['marked']);
    }

    public function test_the_school_calendar_widget_appears_on_every_dashboard_except_the_students(): void
    {
        CalendarEvent::create(['term_id' => $this->term->id, 'date' => now()->addDays(2)->toDateString(), 'title' => 'Founders Day', 'type' => 'event']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('Founders Day')->assertSee('School Calendar');

        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());
        $this->actingAs($registrar)->get(route('registrar.dashboard'))->assertSee('Founders Day');

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T-widget', 'status' => 'active', 'hire_date' => '2020-01-01']);
        $this->actingAs($teacherUser)->get(route('teacher.dashboard'))->assertSee('Founders Day');

        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S-widget', 'first_name' => 'Widget', 'last_name' => 'Test',
            'dob' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2024-01-01',
        ]);
        $this->actingAs($studentUser)->get(route('student.dashboard'))->assertDontSee('Founders Day')->assertDontSee('School Calendar');
    }
}
