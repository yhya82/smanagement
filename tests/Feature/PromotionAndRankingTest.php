<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\Promotions\Index as PromotionsIndex;
use App\Livewire\Admin\PromotionRules\Index as PromotionRulesIndex;
use App\Livewire\Admin\Rankings\Index as RankingsIndex;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\PromotionRule;
use App\Models\ResultEntry;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionAndRankingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private GradeLevel $gradeLevel1;

    private SchoolClass $classA;

    private SchoolClass $classB;

    private Term $term;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $this->gradeLevel1 = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $gradeLevel2 = GradeLevel::create(['name' => 'Primary 2', 'sort_order' => 2]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->classA = SchoolClass::create(['grade_level_id' => $this->gradeLevel1->id, 'academic_year_id' => $year->id, 'name' => 'Class A']);
        $this->classB = SchoolClass::create(['grade_level_id' => $gradeLevel2->id, 'academic_year_id' => $year->id, 'name' => 'Class B']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        $this->student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $this->classA->id,
        ]);

        Enrollment::create([
            'student_id' => $this->student->id, 'class_id' => $this->classA->id, 'academic_year_id' => $year->id,
            'enrollment_date' => '2024-01-01', 'status' => 'active', 'source' => 'individual',
        ]);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $subject->id, 'class_id' => $this->classA->id,
            'term_id' => $this->term->id, 'score' => 90, 'max_score' => 100, 'status' => ResultStatus::Approved,
            'entered_by' => $teacher->id,
        ]);
    }

    public function test_admin_can_compute_rankings_for_a_term(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RankingsIndex::class)
            ->set('termId', (string) $this->term->id)
            ->call('compute')
            ->assertSee('Computed 1 ranking');

        $ranking = TermRanking::where('student_id', $this->student->id)->where('term_id', $this->term->id)->firstOrFail();
        $this->assertSame(1, $ranking->position);
        $this->assertSame('90.00', $ranking->average);
    }

    public function test_computing_rankings_for_the_same_class_and_term_concurrently_is_blocked_not_raced(): void
    {
        // Simulates a second admin's "Compute" click arriving while the
        // first is still running: the lock computeForClassTerm() takes
        // must make the second caller wait/fail rather than both racing
        // to write overlapping rankings.
        $lock = \Illuminate\Support\Facades\Cache::lock("ranking-compute:{$this->classA->id}:{$this->term->id}", 30);
        $this->assertTrue($lock->get(), 'Test setup: expected to acquire the lock first.');

        $this->expectException(\Illuminate\Contracts\Cache\LockTimeoutException::class);

        app(\App\Services\RankingService::class)->computeForClassTerm($this->classA, $this->term);
    }

    public function test_admin_can_create_toggle_and_delete_a_promotion_rule(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PromotionRulesIndex::class)
            ->set('grade_level_id', (string) $this->gradeLevel1->id)
            ->set('criteria_type', 'average_threshold')
            ->set('threshold_value', '50')
            ->set('target_class_id', (string) $this->classB->id)
            ->call('create')
            ->assertHasNoErrors();

        $rule = PromotionRule::where('grade_level_id', $this->gradeLevel1->id)->firstOrFail();
        $this->assertTrue($rule->is_active);

        Livewire::actingAs($this->admin)->test(PromotionRulesIndex::class)->call('toggleActive', $rule->id);
        $this->assertFalse($rule->fresh()->is_active);

        Livewire::actingAs($this->admin)->test(PromotionRulesIndex::class)->call('delete', $rule->id);
        $this->assertSame(0, PromotionRule::count());
    }

    public function test_admin_can_evaluate_a_class_and_approve_the_resulting_promotion(): void
    {
        app(\App\Services\RankingService::class)->computeForClassTerm($this->classA, $this->term);

        $rule = PromotionRule::create([
            'grade_level_id' => $this->gradeLevel1->id, 'criteria_type' => 'average_threshold',
            'threshold_value' => 50, 'target_class_id' => $this->classB->id, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PromotionsIndex::class)
            ->set('showEvaluateForm', true)
            ->set('evaluate_class_id', (string) $this->classA->id)
            ->set('evaluate_term_id', (string) $this->term->id)
            ->call('evaluateClass')
            ->assertSee('Created 1 pending promotion');

        $promotion = Promotion::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('pending', $promotion->status->value);
        $this->assertSame($rule->id, $promotion->promotion_rule_id);

        Livewire::actingAs($this->admin)
            ->test(PromotionsIndex::class)
            ->call('approve', $promotion->id)
            ->assertHasNoErrors();

        $this->assertSame('approved', $promotion->fresh()->status->value);
        $this->assertSame($this->classB->id, $this->student->fresh()->current_class_id);
        $this->assertTrue($this->student->user->notifications()->where('type', 'student_promoted')->exists());
        $this->assertTrue(
            $this->student->enrollments()->where('class_id', $this->classA->id)->where('status', 'transferred')->exists()
        );
        $this->assertTrue(
            $this->student->enrollments()->where('class_id', $this->classB->id)->where('status', 'active')->exists()
        );
    }

    public function test_admin_can_create_and_reject_a_manual_promotion(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PromotionsIndex::class)
            ->set('manual_student_id', (string) $this->student->id)
            ->set('manual_class_id', (string) $this->classB->id)
            ->set('manual_term_id', (string) $this->term->id)
            ->call('createManual')
            ->assertHasNoErrors();

        $promotion = Promotion::where('student_id', $this->student->id)->firstOrFail();
        $this->assertNull($promotion->promotion_rule_id);

        Livewire::actingAs($this->admin)
            ->test(PromotionsIndex::class)
            ->call('reject', $promotion->id);

        $this->assertSame('rejected', $promotion->fresh()->status->value);
        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id, 'rejected promotion must not move the student');
        $this->assertTrue($this->student->user->notifications()->where('type', 'promotion_rejected')->exists());
    }

    public function test_registrar_cannot_access_promotions_rankings_or_rules(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)->get(route('admin.promotions.index'))->assertForbidden();
        $this->actingAs($registrar)->get(route('admin.promotion-rules.index'))->assertForbidden();
        $this->actingAs($registrar)->get(route('admin.rankings.index'))->assertForbidden();
    }
}
