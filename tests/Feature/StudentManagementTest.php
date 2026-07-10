<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\Students\Index as StudentsIndex;
use App\Livewire\Admin\Students\Show as StudentsShow;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolClass $classA;

    private SchoolClass $classB;

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
        $this->classA = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class A']);
        $this->classB = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class B']);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        $this->student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active,
            'current_class_id' => $this->classA->id,
        ]);

        \App\Models\Enrollment::create([
            'student_id' => $this->student->id, 'class_id' => $this->classA->id, 'academic_year_id' => $year->id,
            'enrollment_date' => '2024-01-01', 'status' => 'active', 'source' => 'individual',
        ]);
    }

    public function test_admin_can_search_the_student_directory(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentsIndex::class)
            ->set('search', 'Test')
            ->assertSee('Test Student')
            ->set('search', 'Nonexistent')
            ->assertDontSee('Test Student');
    }

    public function test_admin_can_transfer_a_student_to_another_class(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentsShow::class, ['student' => $this->student])
            ->set('newClassId', (string) $this->classB->id)
            ->call('transfer')
            ->assertHasNoErrors();

        $this->assertSame($this->classB->id, $this->student->fresh()->current_class_id);

        // old enrollment closed out, new one opened - history preserved
        $this->assertTrue(
            \App\Models\Enrollment::where('student_id', $this->student->id)
                ->where('class_id', $this->classA->id)
                ->where('status', 'transferred')
                ->whereNotNull('exit_date')
                ->exists()
        );
        $this->assertTrue(
            \App\Models\Enrollment::where('student_id', $this->student->id)
                ->where('class_id', $this->classB->id)
                ->where('status', 'active')
                ->exists()
        );
    }

    public function test_admin_cannot_transfer_a_student_into_a_full_class(): void
    {
        $this->classB->update(['capacity' => 0]);

        Livewire::actingAs($this->admin)
            ->test(StudentsShow::class, ['student' => $this->student])
            ->set('newClassId', (string) $this->classB->id)
            ->call('transfer')
            ->assertSet('transferError', fn ($value) => str_contains($value, 'full capacity'));

        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id);
    }

    public function test_admin_can_change_a_students_status(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentsShow::class, ['student' => $this->student])
            ->call('changeStatus', 'withdrawn');

        $this->assertSame('withdrawn', $this->student->fresh()->status->value);
    }

    public function test_admin_can_create_and_update_a_health_record(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(StudentsShow::class, ['student' => $this->student]);

        // First save creates the record - creation is never audited (Phase 9:
        // there's no prior state to diff against), so no audit row yet.
        $component->set('allergies', 'Peanuts')
            ->set('emergencyNotes', 'Call mother immediately')
            ->call('saveHealthRecord')
            ->assertHasNoErrors();

        $record = $this->student->healthRecord()->firstOrFail();
        $this->assertSame('Peanuts', $record->allergies);
        $this->assertSame($this->admin->id, $record->updated_by);
        $this->assertSame(0, \App\Models\HealthRecordAudit::where('health_record_id', $record->id)->count());

        // Second save updates the existing record - this change IS audited
        // (SRS §11).
        $component->set('allergies', 'Peanuts, Shellfish')
            ->call('saveHealthRecord')
            ->assertHasNoErrors();

        $this->assertSame('Peanuts, Shellfish', $record->fresh()->allergies);
        $this->assertTrue(
            \App\Models\HealthRecordAudit::where('health_record_id', $record->id)->exists()
        );
    }

    public function test_teacher_cannot_access_student_management(): void
    {
        $teacherUser = User::create(['name' => 'T', 'email' => 't@test.com', 'password' => 'x', 'status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());

        $this->actingAs($teacherUser)
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }
}
