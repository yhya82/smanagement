<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Notifications\ResultApproved;
use RuntimeException;

class ResultService
{
    public function saveDraft(
        Student $student,
        Subject $subject,
        SchoolClass $class,
        Term $term,
        float $score,
        float $maxScore,
        Teacher $enteredBy
    ): ResultEntry {
        if (! $enteredBy->hasAccessToClassSubject($class->id, $subject->id)) {
            throw new RuntimeException('Teacher is not assigned to this class/subject.');
        }

        return ResultEntry::updateOrCreate(
            ['student_id' => $student->id, 'subject_id' => $subject->id, 'term_id' => $term->id],
            [
                'class_id' => $class->id,
                'entered_by' => $enteredBy->id,
                'score' => $score,
                'max_score' => $maxScore,
                'status' => ResultStatus::Draft,
            ]
        );
    }

    public function submit(ResultEntry $entry): ResultEntry
    {
        if ($entry->status !== ResultStatus::Draft) {
            throw new RuntimeException('Only draft results can be submitted.');
        }

        $entry->update(['status' => ResultStatus::Submitted]);

        return $entry;
    }

    /**
     * SRS §17: "Students only see approved results and receive
     * notifications" - the notification fires here, at the moment of
     * approval, not at submission.
     */
    public function approve(ResultEntry $entry, User $approvedBy): ResultEntry
    {
        if ($entry->status !== ResultStatus::Submitted) {
            throw new RuntimeException('Only submitted results can be approved.');
        }

        $entry->update([
            'status' => ResultStatus::Approved,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]);

        $entry->student->user->notify(new ResultApproved($entry));

        return $entry;
    }

    /**
     * Rejected results go back to the teacher to correct and resubmit -
     * not deleted (SRS §22: status over hard delete).
     */
    public function reject(ResultEntry $entry, User $rejectedBy): ResultEntry
    {
        if ($entry->status !== ResultStatus::Submitted) {
            throw new RuntimeException('Only submitted results can be rejected.');
        }

        $entry->update([
            'status' => ResultStatus::Rejected,
            'approved_by' => $rejectedBy->id,
            'approved_at' => now(),
        ]);

        return $entry;
    }
}
