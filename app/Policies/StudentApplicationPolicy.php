<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\StudentApplication;
use App\Models\User;

class StudentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('applications.create') || $user->hasPermission('applications.approve');
    }

    public function view(User $user, StudentApplication $application): bool
    {
        return $user->hasPermission('applications.create') || $user->hasPermission('applications.approve');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('applications.create');
    }

    public function update(User $user, StudentApplication $application): bool
    {
        return $user->hasPermission('applications.create') && $application->status === ApprovalStatus::Pending;
    }

    /**
     * Applications are never deleted (SRS §22: status over hard delete) -
     * a mistaken one gets rejected, not removed.
     */
    public function delete(User $user, StudentApplication $application): bool
    {
        return false;
    }

    public function approve(User $user, StudentApplication $application): bool
    {
        return $user->hasPermission('applications.approve') && $application->status === ApprovalStatus::Pending;
    }

    public function reject(User $user, StudentApplication $application): bool
    {
        return $user->hasPermission('applications.approve') && $application->status === ApprovalStatus::Pending;
    }
}
