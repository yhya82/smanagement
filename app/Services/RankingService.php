<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TermRanking;
use Illuminate\Support\Collection;

class RankingService
{
    /**
     * Per-term batch computation (SRS §18): averages each student's approved
     * subject scores as a percentage, then ranks with standard competition
     * ranking - "equal scores share positions" (§18), so ties get the same
     * position and the next distinct value skips by the tied count
     * (1, 1, 3, 4 - not 1, 1, 2, 3).
     */
    public function computeForClassTerm(SchoolClass $class, Term $term): Collection
    {
        $sorted = ResultEntry::query()
            ->where('class_id', $class->id)
            ->where('term_id', $term->id)
            ->where('status', ResultStatus::Approved)
            ->get()
            ->groupBy('student_id')
            ->map(fn ($entries) => round($entries->avg(fn ($entry) => ($entry->score / $entry->max_score) * 100), 2))
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
