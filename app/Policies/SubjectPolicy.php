<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subject $subject): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subjects.manage');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasPermission('subjects.manage');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasPermission('subjects.manage');
    }
}
