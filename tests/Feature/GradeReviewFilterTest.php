<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\GradeReviewIndex;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\ResultEntry;
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

/**
 * With many classes and subjects in play, the review queue's default flat
 * list mixes every submission together - these filters let an admin narrow
 * to one class/subject at a time instead of hunting through all of them.
 */
class GradeReviewFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_the_review_queue_by_class_and_subject(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);

        $classA = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class A']);
        $classB = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class B']);
        $subjectMath = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        $subjectEnglish = Subject::create(['name' => 'English', 'code' => 'ENG1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $studentA = $this->makeStudent('Alice', $classA->id);
        $studentB = $this->makeStudent('Bob', $classB->id);

        ResultEntry::create([
            'student_id' => $studentA->id, 'subject_id' => $subjectMath->id, 'class_id' => $classA->id,
            'term_id' => $term->id, 'score' => 70, 'max_score' => 100, 'status' => ResultStatus::Submitted,
            'entered_by' => $teacher->id,
        ]);

        ResultEntry::create([
            'student_id' => $studentB->id, 'subject_id' => $subjectEnglish->id, 'class_id' => $classB->id,
            'term_id' => $term->id, 'score' => 80, 'max_score' => 100, 'status' => ResultStatus::Submitted,
            'entered_by' => $teacher->id,
        ]);

        $component = Livewire::actingAs($admin)->test(GradeReviewIndex::class)
            ->assertSee('Alice')
            ->assertSee('Bob');

        $component->set('classId', (string) $classA->id)
            ->assertSee('Alice')
            ->assertDontSee('Bob');

        $component->set('classId', '')
            ->set('subjectId', (string) $subjectEnglish->id)
            ->assertSee('Bob')
            ->assertDontSee('Alice');
    }

    private function makeStudent(string $firstName, int $classId): Student
    {
        $user = User::create([
            'name' => $firstName, 'email' => strtolower($firstName).'@test.com', 'password' => 'x', 'status' => UserStatus::Active,
        ]);

        return Student::create([
            'user_id' => $user->id, 'student_no' => 'S-'.$firstName, 'first_name' => $firstName, 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $classId,
        ]);
    }
}
