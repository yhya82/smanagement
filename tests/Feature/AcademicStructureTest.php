<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\AcademicYears\Index as AcademicYearsIndex;
use App\Livewire\Admin\Classes\Index as ClassesIndex;
use App\Livewire\Admin\Classes\Subjects as ClassSubjects;
use App\Livewire\Admin\GradeLevels\Index as GradeLevelsIndex;
use App\Livewire\Admin\Subjects\Index as SubjectsIndex;
use App\Livewire\Admin\Terms\Index as TermsIndex;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());
    }

    public function test_admin_can_create_and_activate_an_academic_year(): void
    {
        $component = Livewire::actingAs($this->admin)->test(AcademicYearsIndex::class);

        $component->set('name', '2026/2027')
            ->set('start_date', '2026-09-01')
            ->set('end_date', '2027-07-31')
            ->call('create')
            ->assertHasNoErrors();

        $year = AcademicYear::where('name', '2026/2027')->firstOrFail();
        $this->assertFalse($year->is_active);

        $component->call('activate', $year->id);

        $this->assertTrue($year->fresh()->is_active);
    }

    public function test_activating_a_year_deactivates_the_previous_one(): void
    {
        $old = AcademicYear::create(['name' => 'Old', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_active' => true]);
        $new = AcademicYear::create(['name' => 'New', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => false]);

        Livewire::actingAs($this->admin)
            ->test(AcademicYearsIndex::class)
            ->call('activate', $new->id);

        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($new->fresh()->is_active);
    }

    public function test_admin_can_create_and_activate_a_term(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);

        $component = Livewire::actingAs($this->admin)->test(TermsIndex::class);

        $component->set('academic_year_id', (string) $year->id)
            ->set('name', 'Term 1')
            ->set('start_date', '2026-09-01')
            ->set('end_date', '2026-12-12')
            ->call('create')
            ->assertHasNoErrors();

        $term = Term::where('name', 'Term 1')->firstOrFail();
        $component->call('activate', $term->id);

        $this->assertTrue($term->fresh()->is_active);
    }

    public function test_admin_can_create_a_grade_level_with_auto_suggested_sort_order(): void
    {
        GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);

        $component = Livewire::actingAs($this->admin)->test(GradeLevelsIndex::class);

        $this->assertSame(2, $component->get('sort_order'));

        $component->set('name', 'Primary 2')->call('create')->assertHasNoErrors();

        $this->assertTrue(GradeLevel::where('name', 'Primary 2')->where('sort_order', 2)->exists());
    }

    public function test_admin_can_create_a_subject(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SubjectsIndex::class)
            ->set('name', 'Mathematics')
            ->set('code', 'MATH1')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertTrue(Subject::where('code', 'MATH1')->exists());
    }

    public function test_admin_can_create_a_class_defaulting_to_the_active_year(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);

        $component = Livewire::actingAs($this->admin)->test(ClassesIndex::class);

        $this->assertSame((string) $year->id, $component->get('academic_year_id'));

        $component->set('grade_level_id', (string) $gradeLevel->id)
            ->set('name', 'Blue Stream')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertTrue(SchoolClass::where('name', 'Blue Stream')->where('academic_year_id', $year->id)->exists());
    }

    public function test_admin_can_assign_and_remove_a_subject_from_a_class(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $component = Livewire::actingAs($this->admin)->test(ClassSubjects::class, ['class' => $class]);

        $component->set('subject_id', (string) $subject->id)
            ->set('term_id', (string) $term->id)
            ->call('assign')
            ->assertHasNoErrors();

        $assignment = ClassSubject::where('class_id', $class->id)->where('subject_id', $subject->id)->firstOrFail();

        // duplicate assignment for the same class/subject/term is rejected
        $component->set('subject_id', (string) $subject->id)
            ->set('term_id', (string) $term->id)
            ->call('assign')
            ->assertHasErrors('subject_id');

        $component->call('remove', $assignment->id);
        $this->assertSame(0, ClassSubject::where('class_id', $class->id)->count());
    }
}
