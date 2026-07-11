<?php

namespace App\Services;

use App\Enums\ExamType;
use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Term;
use App\Models\TermRanking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RankingService
{
    /**
     * Per-term batch computation (SRS §18): averages each student's approved
     * subject scores as a percentage, then ranks with standard competition
     * ranking - "equal scores share positions" (§18), so ties get the same
     * position and the next distinct value skips by the tied count
     * (1, 1, 3, 4 - not 1, 1, 2, 3).
     *
     * Midterm and final are approved independently, so a subject may have
     * one, the other, or both approved at compute time. Both approved ->
     * combine with the school's configured weights; only one approved -> use
     * it alone as an interim subject score (don't make a student wait for
     * the final to see where they stand).
     *
     * A lock (not just a transaction) guards the whole computation: two
     * admins clicking "Compute" for the same class/term at once would
     * otherwise both read the same result rows and race to write
     * overlapping rankings, colliding on TermRanking's unique constraint
     * unpredictably rather than one simply running after the other. The
     * transaction inside it is what keeps a crash mid-loop from leaving
     * some students freshly ranked and others holding a stale prior result.
     */
    public function computeForClassTerm(SchoolClass $class, Term $term): Collection
    {
        return Cache::lock("ranking-compute:{$class->id}:{$term->id}", 30)->block(3, fn () => DB::transaction(
            fn () => $this->compute($class, $term)
        ));
    }

    private function compute(SchoolClass $class, Term $term): Collection
    {
        $weights = SchoolSetting::current();

        $sorted = ResultEntry::query()
            ->where('class_id', $class->id)
            ->where('term_id', $term->id)
            ->where('status', ResultStatus::Approved)
            ->get()
            ->groupBy('student_id')
            ->map(function (Collection $entries) use ($weights) {
                $subjectPercentages = $entries->groupBy('subject_id')->map(function (Collection $subjectEntries) use ($weights) {
                    $midterm = $subjectEntries->firstWhere('exam_type', ExamType::Midterm);
                    $final = $subjectEntries->firstWhere('exam_type', ExamType::Final);

                    if ($midterm && $final) {
                        return $midterm->percentage() * ($weights->midterm_weight / 100)
                            + $final->percentage() * ($weights->final_weight / 100);
                    }

                    return ($midterm ?? $final)->percentage();
                });

                return round($subjectPercentages->avg(), 2);
            })
            ->sortDesc();

        $rows = $sorted->map(fn ($average, $studentId) => ['student_id' => $studentId, 'average' => $average])->values()->all();

        // Plain array, not a Collection: the tie-back-fill below needs to
        // mutate an already-pushed entry in place (Collection elements
        // aren't modifiable by reference through array access).
        $rankings = [];

        foreach ($rows as $index => $row) {
            if ($index > 0 && $row['average'] === $rows[$index - 1]['average']) {
                $position = $rankings[$index - 1]['position'];
                $rankings[$index - 1]['is_tied'] = true;
                $isTied = true;
            } else {
                $position = $index + 1;
                $isTied = false;
            }

            $rankings[] = [
                'student_id' => $row['student_id'],
                'average' => $row['average'],
                'position' => $position,
                'is_tied' => $isTied,
            ];
        }

        foreach ($rankings as $ranking) {
            TermRanking::updateOrCreate(
                ['student_id' => $ranking['student_id'], 'term_id' => $term->id],
                [
                    'class_id' => $class->id,
                    'average' => $ranking['average'],
                    'position' => $ranking['position'],
                    'is_tied' => $ranking['is_tied'],
                    'computed_at' => now(),
                ]
            );
        }

        return collect($rankings);
    }
}
