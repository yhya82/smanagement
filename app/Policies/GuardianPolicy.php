<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\Guardian;
use App\Models\User;

class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('applications.create')
            || $user->hasPermission('applications.approve')
            || $user->hasPermission('students.manage')
            || $user->hasPermission('students.view');
    }

    public function view(User $user, Guardian $guardian): bool
    {
        if ($guardian->student_id !== null && $user->student?->id === $guardian->student_id) {
            return true;
        }

        return $user->hasPermission('applications.create')
            || $user->hasPermission('applications.approve')
            || $user->hasPermission('students.manage')
            || $user->hasPermission('students.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('applications.create');
    }

    /**
     * Editable while the application is still pending (registrar fixing the
     * record), or afterwards via students.manage once the guardian is
     * attached to an active student.
     */
    public function update(User $user, Guardian $guardian): bool
    {
        if ($guardian->student_id === null) {
            return $user->hasPermission('applications.create')
                && $guardian->studentApplication->status === ApprovalStatus::Pending;
        }

        return $user->hasPermission('students.manage');
    }

    /**
     * At least one guardian must remain per application/student (SRS §10) -
     * that "≥1 required" invariant is enforced by the admission service, not
     * here, but deletion still requires the same permission as editing.
     */
    public function delete(User $user, Guardian $guardian): bool
    {
        return $this->update($user, $guardian);
    }
}
