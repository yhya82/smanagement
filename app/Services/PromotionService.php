<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\PromotionCriteriaType;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\PromotionRule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use App\Notifications\StudentPromoted;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromotionService
{
    /**
     * Evaluates the student's grade level's promotion_rules against their
     * term ranking; still just creates a *pending* Promotion even when a
     * rule matches - promotion always requires administrator approval
     * regardless of how it was triggered (SRS §18).
     */
    public function evaluate(Student $student, Term $term, User $requestedBy): ?Promotion
    {
        if ($student->status !== StudentStatus::Active) {
            throw new RuntimeException('Only active students are eligible for promotion.');
        }

        $currentClass = $student->currentClass;
        $ranking = TermRanking::where('student_id', $student->id)->where('term_id', $term->id)->first();

        if (! $currentClass || ! $ranking) {
            return null;
        }

        $rule = PromotionRule::where('grade_level_id', $currentClass->grade_level_id)
            ->where('is_active', true)
            ->whereNotNull('target_class_id')
            ->get()
            ->first(fn (PromotionRule $rule) => $this->meetsCriteria($rule, $ranking));

        if (! $rule) {
            return null;
        }

        return Promotion::create([
            'student_id' => $student->id,
            'from_class_id' => $currentClass->id,
            'to_class_id' => $rule->target_class_id,
            'term_id' => $term->id,
            'promotion_rule_id' => $rule->id,
            'status' => ApprovalStatus::Pending,
            'requested_by' => $requestedBy->id,
        ]);
    }

    private function meetsCriteria(PromotionRule $rule, TermRanking $ranking): bool
    {
        return match ($rule->criteria_type) {
            PromotionCriteriaType::RankThreshold => $ranking->position <= $rule->threshold_value,
            PromotionCriteriaType::AverageThreshold => $ranking->average >= $rule->threshold_value,
        };
    }

    /**
     * Manual, non-rule-driven promotion - promotion_rule_id stays null but
     * still requires the same admin approval as a rule-driven one.
     */
    public function createManual(Student $student, SchoolClass $toClass, Term $term, User $requestedBy): Promotion
    {
        if ($student->status !== StudentStatus::Active) {
            throw new RuntimeException('Only active students are eligible for promotion.');
        }

        return Promotion::create([
            'student_id' => $student->id,
            'from_class_id' => $student->current_class_id,
            'to_class_id' => $toClass->id,
            'term_id' => $term->id,
            'promotion_rule_id' => null,
            'status' => ApprovalStatus::Pending,
            'requested_by' => $requestedBy->id,
        ]);
    }

    public function approve(Promotion $promotion, User $approvedBy): Student
    {
        if ($promotion->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('This promotion has already been decided.');
        }

        return DB::transaction(function () use ($promotion, $approvedBy) {
            $promotion->update([
                'status' => ApprovalStatus::Approved,
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);

            $student = $promotion->student;

            Enrollment::where('student_id', $student->id)
                ->where('class_id', $promotion->from_class_id)
                ->where('status', EnrollmentStatus::Active)
                ->update(['status' => EnrollmentStatus::Transferred, 'exit_date' => now()]);

            $toClass = $promotion->toClass;
            $student->update(['current_class_id' => $toClass->id]);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $toClass->id,
                'academic_year_id' => $toClass->academic_year_id,
                'enrollment_date' => now(),
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::Individual,
            ]);

            $student->user->notify(new StudentPromoted($promotion));

            return $student;
        });
    }

    public function reject(Promotion $promotion, User $rejectedBy): Promotion
    {
        if ($promotion->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('This promotion has already been decided.');
        }

        $promotion->update([
            'status' => ApprovalStatus::Rejected,
            'approved_by' => $rejectedBy->id,
            'approved_at' => now(),
        ]);

        return $promotion;
    }
}
