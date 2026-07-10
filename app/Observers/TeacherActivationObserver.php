<?php

namespace App\Observers;

use App\Enums\TeacherStatus;
use App\Models\TeacherSubjectAssignment;

/**
 * SRS §12: teachers become active after at least one subject assignment.
 * Flips the moment the first teacher_subject_assignments row is created,
 * rather than requiring a separate manual activation step.
 */
class TeacherActivationObserver
{
    public function created(TeacherSubjectAssignment $assignment): void
    {
        $teacher = $assignment->teacher;

        if ($teacher->status === TeacherStatus::Pending) {
            $teacher->update(['status' => TeacherStatus::Active]);
        }
    }
}
