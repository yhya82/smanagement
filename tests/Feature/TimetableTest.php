<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Livewire\Admin\Classes\Subjects as ClassSubjectsComponent;
use App\Livewire\Admin\Periods\Index as PeriodsIndex;
use App\Livewire\Admin\Timetable\Show as TimetableShow;
use App\Livewire\Student\Timetable as StudentTimetable;
use App\Livewire\Teacher\Timetable as TeacherTimetable;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeLevel;
use App\Models\Period;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\TimetableService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $year;

    private Term $term;

    private GradeLevel $gradeLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $this->year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $this->year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
    }

    private function makeClass(string $name = 'Blue Stream'): SchoolClass
    {
        return SchoolClass::create(['grade_level_id' => $this->gradeLevel->id, 'academic_year_id' => $this->year->id, 'name' => $name]);
    }

    private function makeTeacherAssignedTo(SchoolClass $class, Subject $subject): Teacher
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->roles()->attach(Role::where('name', 'Teacher')->first());
        $teacher = Teacher::create(['user_id' => $user->id, 'employee_no' => 'T-'.$class->id.'-'.$subject->id, 'status' => 'active', 'hire_date' => '2020-01-01']);

        ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'term_id' => $this->term->id]);
        TeacherSubjectAssignment::create([
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
            'term_id' => $this->term->id, 'is_active' => true,
        ]);

        return $teacher;
    }

    private function makePeriods(int $count = 3): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Period::create(['name' => "Period {$i}", 'start_time' => sprintf('%02d:00', 7 + $i), 'end_time' => sprintf('%02d:00', 8 + $i), 'sort_order' => $i]);
        }
    }

    public function test_admin_can_create_a_period(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PeriodsIndex::class)
            ->set('name', 'Period 1')
            ->set('start_time', '08:00')
            ->set('end_time', '08:40')
            ->set('sort_order', 1)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertTrue(Period::where('name', 'Period 1')->exists());
    }

    public function test_generate_throws_when_class_has_no_subjects(): void
    {
        $this->makePeriods();
        $class = $this->makeClass();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no subjects assigned');

        app(TimetableService::class)->generate($class, $this->term);
    }

    public function test_generate_throws_when_no_subject_has_a_teacher(): void
    {
        $this->makePeriods();
        $class = $this->makeClass();
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        ClassSubject::create(['class_id' => $class->id, 'subject_id' => $subject->id, 'term_id' => $this->term->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('teacher');

        app(TimetableService::class)->generate($class, $this->term);
    }

    public function test_generate_throws_when_no_periods_configured(): void
    {
        $class = $this->makeClass();
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $this->makeTeacherAssignedTo($class, $subject);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('periods');

        app(TimetableService::class)->generate($class, $this->term);
    }

    public function test_generate_round_robins_subjects_across_all_empty_slots(): void
    {
        $this->makePeriods(2);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG1']);
        $this->makeTeacherAssignedTo($class, $math);
        $this->makeTeacherAssignedTo($class, $english);

        $result = app(TimetableService::class)->generate($class, $this->term);

        // 5 days x 2 periods = 10 slots, all fillable since teachers are on different classes.
        $this->assertSame(10, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(10, TimetableEntry::where('class_id', $class->id)->count());
    }

    public function test_generate_query_count_does_not_scale_with_slot_count(): void
    {
        // Deliberately larger than the other tests' fixtures - the old
        // implementation queried once per subject-attempt per slot, so this
        // shape (5 days x 6 periods x up to 4 subject attempts, plus another
        // class in the same term to populate the "busy elsewhere" check)
        // would have run into the dozens of queries under the old code.
        $this->makePeriods(6);
        $class = $this->makeClass('Class A');
        $otherClass = $this->makeClass('Class B');

        foreach (['Mathematics', 'English', 'Science', 'History'] as $name) {
            $subject = Subject::create(['name' => $name, 'code' => strtoupper(substr($name, 0, 4)).'1']);
            $this->makeTeacherAssignedTo($class, $subject);
        }

        // A second class with its own teacher assignment/timetable so the
        // "busy elsewhere" map actually has cross-class data to build, not
        // an empty one.
        $otherSubject = Subject::create(['name' => 'Art', 'code' => 'ART1']);
        $this->makeTeacherAssignedTo($otherClass, $otherSubject);
        app(TimetableService::class)->generate($otherClass, $this->term);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $result = app(TimetableService::class)->generate($class, $this->term);
        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // One INSERT per created slot is unavoidable - what the fix removes
        // is the *extra* read queries the old per-attempt busy-check made on
        // top of that (up to subjectCount x 2 extra queries per slot, before
        // even counting the insert). A handful of fixed setup reads plus one
        // insert per row is the expected shape now.
        $this->assertLessThanOrEqual(
            $result['created'] + 8,
            $queryCount,
            "Expected roughly one query per created slot plus fixed overhead; saw {$queryCount} queries for {$result['created']} created slots."
        );
    }

    public function test_regenerate_only_fills_empty_slots_and_keeps_manual_edits(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG1']);
        $this->makeTeacherAssignedTo($class, $math);
        $this->makeTeacherAssignedTo($class, $english);

        $period = Period::first();

        // Manually set Monday's single period to English before generating.
        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', $english, $this->admin);

        app(TimetableService::class)->generate($class, $this->term);

        $mondayEntry = TimetableEntry::where('class_id', $class->id)->where('day_of_week', 'monday')->where('period_id', $period->id)->first();
        $this->assertSame($english->id, $mondayEntry->subject_id);

        // Re-running generate again must not create duplicates or change the manual slot.
        $before = TimetableEntry::where('class_id', $class->id)->count();
        app(TimetableService::class)->generate($class, $this->term);
        $this->assertSame($before, TimetableEntry::where('class_id', $class->id)->count());
        $this->assertSame($english->id, $mondayEntry->fresh()->subject_id);
    }

    public function test_generate_skips_a_slot_when_only_schedulable_subjects_teacher_is_already_busy(): void
    {
        $this->makePeriods(1);
        $classA = $this->makeClass('Class A');
        $classB = $this->makeClass('Class B');
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T-shared', 'status' => 'active', 'hire_date' => '2020-01-01']);

        foreach ([$classA, $classB] as $class) {
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $math->id, 'term_id' => $this->term->id]);
            TeacherSubjectAssignment::create([
                'teacher_id' => $teacher->id, 'subject_id' => $math->id, 'class_id' => $class->id,
                'term_id' => $this->term->id, 'is_active' => true,
            ]);
        }

        $period = Period::first();
        app(TimetableService::class)->setEntry($classA, $this->term, $period, 'monday', $math, $this->admin);

        $result = app(TimetableService::class)->generate($classB, $this->term);

        // 5 days x 1 period = 5 slots; only Monday's is blocked (teacher already
        // booked in Class A there), the other 4 days schedule normally.
        $this->assertSame(4, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertFalse(TimetableEntry::where('class_id', $classB->id)->where('day_of_week', 'monday')->exists());
    }

    public function test_set_entry_rejects_double_booking_the_same_teacher_in_another_class(): void
    {
        $this->makePeriods(1);
        $classA = $this->makeClass('Class A');
        $classB = $this->makeClass('Class B');
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T-shared', 'status' => 'active', 'hire_date' => '2020-01-01']);

        foreach ([$classA, $classB] as $class) {
            ClassSubject::create(['class_id' => $class->id, 'subject_id' => $math->id, 'term_id' => $this->term->id]);
            TeacherSubjectAssignment::create([
                'teacher_id' => $teacher->id, 'subject_id' => $math->id, 'class_id' => $class->id,
                'term_id' => $this->term->id, 'is_active' => true,
            ]);
        }

        $period = Period::first();
        app(TimetableService::class)->setEntry($classA, $this->term, $period, 'monday', $math, $this->admin);

        $this->expectException(RuntimeException::class);
        app(TimetableService::class)->setEntry($classB, $this->term, $period, 'monday', $math, $this->admin);
    }

    public function test_set_entry_with_null_subject_clears_the_slot(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $this->makeTeacherAssignedTo($class, $math);
        $period = Period::first();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', $math, $this->admin);
        $this->assertSame(1, TimetableEntry::where('class_id', $class->id)->count());

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', null, $this->admin);
        $this->assertSame(0, TimetableEntry::where('class_id', $class->id)->count());
    }

    public function test_clearing_a_slot_notifies_the_teacher_it_was_cleared_not_reassigned(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $teacher = $this->makeTeacherAssignedTo($class, $math);
        $period = Period::first();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', $math, $this->admin);
        $teacher->user->notifications()->delete();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', null, $this->admin);

        $notification = $teacher->user->notifications()->where('type', 'timetable_changed')->firstOrFail();
        $this->assertStringContainsString('cleared', $notification->message);
        $this->assertStringNotContainsString('is now Mathematics', $notification->message);
    }

    public function test_removing_a_subject_from_a_class_deletes_its_orphaned_timetable_entries(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $this->makeTeacherAssignedTo($class, $math);
        $period = Period::first();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'monday', $math, $this->admin);
        $this->assertSame(1, TimetableEntry::where('class_id', $class->id)->count());

        $classSubject = ClassSubject::where('class_id', $class->id)->where('subject_id', $math->id)->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(ClassSubjectsComponent::class, ['class' => $class])
            ->call('remove', $classSubject->id);

        $this->assertSame(0, TimetableEntry::where('class_id', $class->id)->count());
    }

    public function test_admin_timetable_component_can_generate_and_manually_edit_a_slot(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $this->makeTeacherAssignedTo($class, $math);
        $period = Period::first();

        $component = Livewire::actingAs($this->admin)
            ->test(TimetableShow::class, ['class' => $class])
            ->set('termId', (string) $this->term->id)
            ->call('generate');

        // 5 days x 1 period = 5 slots, all fillable.
        $this->assertSame(5, TimetableEntry::where('class_id', $class->id)->count());

        $component->call('openSlot', 'tuesday', $period->id)
            ->set('editingSubjectId', (string) $math->id)
            ->call('saveSlot');

        $this->assertTrue(TimetableEntry::where([
            'class_id' => $class->id, 'day_of_week' => 'tuesday', 'period_id' => $period->id, 'subject_id' => $math->id,
        ])->exists());
    }

    public function test_teacher_sees_their_own_assigned_classes_timetable(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $teacher = $this->makeTeacherAssignedTo($class, $math);
        $period = Period::first();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'wednesday', $math, $this->admin);

        Livewire::actingAs($teacher->user)
            ->test(TeacherTimetable::class)
            ->assertSee('Mathematics')
            ->assertSee($class->name);
    }

    public function test_student_sees_only_their_own_class_timetable(): void
    {
        $this->makePeriods(1);
        $class = $this->makeClass();
        $otherClass = $this->makeClass('Other Stream');
        $math = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI1']);
        $this->makeTeacherAssignedTo($class, $math);
        $this->makeTeacherAssignedTo($otherClass, $science);
        $period = Period::first();

        app(TimetableService::class)->setEntry($class, $this->term, $period, 'thursday', $math, $this->admin);
        app(TimetableService::class)->setEntry($otherClass, $this->term, $period, 'thursday', $science, $this->admin);

        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        Student::create([
            'user_id' => $studentUser->id,
            'student_no' => 'S-1',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'dob' => '2015-01-01',
            'gender' => Gender::Female,
            'admission_date' => '2024-01-01',
            'current_class_id' => $class->id,
        ]);

        Livewire::actingAs($studentUser)
            ->test(StudentTimetable::class)
            ->assertSee('Mathematics')
            ->assertDontSee('Science');
    }

    public function test_a_teacher_cannot_reach_the_admin_timetable_route_at_all(): void
    {
        $class = $this->makeClass();
        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T-unrelated', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $this->actingAs($teacherUser)
            ->get(route('admin.classes.timetable', $class))
            ->assertForbidden();
    }
}
