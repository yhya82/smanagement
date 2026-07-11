<?php

namespace Tests\Unit;

use App\Enums\Gender;
use App\Enums\PromotionCriteriaType;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\PromotionRule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isolated tests of PromotionService::evaluate()'s rule-matching logic
 * (meetsCriteria() is private, so this exercises it through evaluate()
 * directly rather than through any Livewire component or HTTP request).
 */
class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradeLevel $gradeLevel;

    private SchoolClass $fromClass;

    private SchoolClass $toClass;

    private Term $term;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->fromClass = SchoolClass::create(['grade_level_id' => $this->gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'From Class']);
        $this->toClass = SchoolClass::create(['grade_level_id' => $this->gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'To Class']);
        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
    }

    private function makeStudent(): Student
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        return Student::create([
            'user_id' => $user->id, 'student_no' => 'S-'.uniqid(), 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $this->fromClass->id,
        ]);
    }

    public function test_rank_threshold_rule_matches_when_position_is_at_or_better_than_the_threshold(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::RankThreshold,
            'threshold_value' => 5, 'target_class_id' => $this->toClass->id, 'is_active' => true,
        ]);

        $student = $this->makeStudent();
        TermRanking::create(['student_id' => $student->id, 'class_id' => $this->fromClass->id, 'term_id' => $this->term->id, 'average' => 60, 'position' => 5]);

        $promotion = app(PromotionService::class)->evaluate($student, $this->term, $this->admin);

        $this->assertNotNull($promotion, 'Position 5 should satisfy a rank_threshold of 5.');
        $this->assertSame($this->toClass->id, $promotion->to_class_id);
    }

    public function test_rank_threshold_rule_does_not_match_when_position_is_worse_than_the_threshold(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::RankThreshold,
            'threshold_value' => 5, 'target_class_id' => $this->toClass->id, 'is_active' => true,
        ]);

        $student = $this->makeStudent();
        TermRanking::create(['student_id' => $student->id, 'class_id' => $this->fromClass->id, 'term_id' => $this->term->id, 'average' => 40, 'position' => 6]);

        $promotion = app(PromotionService::class)->evaluate($student, $this->term, $this->admin);

        $this->assertNull($promotion, 'Position 6 must not satisfy a rank_threshold of 5.');
    }

    public function test_average_threshold_rule_matches_when_average_is_at_or_above_the_threshold(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::AverageThreshold,
            'threshold_value' => 50, 'target_class_id' => $this->toClass->id, 'is_active' => true,
        ]);

        $student = $this->makeStudent();
        TermRanking::create(['student_id' => $student->id, 'class_id' => $this->fromClass->id, 'term_id' => $this->term->id, 'average' => 50, 'position' => 20]);

        $promotion = app(PromotionService::class)->evaluate($student, $this->term, $this->admin);

        $this->assertNotNull($promotion, 'An average exactly at the threshold should satisfy average_threshold.');
    }

    public function test_average_threshold_rule_does_not_match_below_the_threshold(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::AverageThreshold,
            'threshold_value' => 50, 'target_class_id' => $this->toClass->id, 'is_active' => true,
        ]);

        $student = $this->makeStudent();
        TermRanking::create(['student_id' => $student->id, 'class_id' => $this->fromClass->id, 'term_id' => $this->term->id, 'average' => 49.99, 'position' => 20]);

        $promotion = app(PromotionService::class)->evaluate($student, $this->term, $this->admin);

        $this->assertNull($promotion);
    }

    public function test_an_inactive_rule_is_never_matched(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::AverageThreshold,
            'threshold_value' => 0, 'target_class_id' => $this->toClass->id, 'is_active' => false,
        ]);

        $student = $this->makeStudent();
        TermRanking::create(['student_id' => $student->id, 'class_id' => $this->fromClass->id, 'term_id' => $this->term->id, 'average' => 99, 'position' => 1]);

        $this->assertNull(app(PromotionService::class)->evaluate($student, $this->term, $this->admin));
    }

    public function test_no_ranking_yet_means_no_promotion(): void
    {
        PromotionRule::create([
            'grade_level_id' => $this->gradeLevel->id, 'criteria_type' => PromotionCriteriaType::AverageThreshold,
            'threshold_value' => 0, 'target_class_id' => $this->toClass->id, 'is_active' => true,
        ]);

        $student = $this->makeStudent();

        $this->assertNull(app(PromotionService::class)->evaluate($student, $this->term, $this->admin));
    }
}
