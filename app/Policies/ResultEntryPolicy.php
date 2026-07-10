<?php

namespace App\Policies;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\User;

class ResultEntryPolicy
{
    /**
     * No permission grants Registrar grade visibility (SRS §7 explicitly
     * excludes editing grades, and nothing grants viewing them either) -
     * scoped to the entering teacher, the student themself (once approved,
     * per SRS §17), and Administrator (Gate::before bypass).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Teacher') || $user->student !== null;
    }

    public function view(User $user, ResultEntry $result): bool
    {
        if ($user->student?->id === $result->student_id) {
            return $result->status === ResultStatus::Approved;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClassSubject($result->class_id, $result->subject_id) ?? false;
        }

        return false;
    }

    public function create(User $user, int $classId, int $subjectId): bool
    {
        return $user->hasPermission('grades.enter')
            && ($user->teacher?->hasAccessToClassSubject($classId, $subjectId) ?? false);
    }

    /**
     * Once approved, a result is locked - correcting it means the
     * approval is reversed first (an admin action), not a direct teacher
     * edit of an already-approved grade.
     */
    public function update(User $user, ResultEntry $result): bool
    {
        if ($result->status === ResultStatus::Approved) {
            return false;
        }

        return $user->hasPermission('grades.enter')
            && ($user->teacher?->hasAccessToClassSubject($result->class_id, $result->subject_id) ?? false);
    }

    public function approve(User $user, ResultEntry $result): bool
    {
        return $user->hasPermission('grades.approve');
    }

    public function delete(User $user, ResultEntry $result): bool
    {
        return false;
    }
}
