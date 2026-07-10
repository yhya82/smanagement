<?php

namespace App\Policies;

use App\Models\ClassSubject;
use App\Models\User;

class ClassSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClassSubject $classSubject): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('classes.manage');
    }

    public function update(User $user, ClassSubject $classSubject): bool
    {
        return $user->hasPermission('classes.manage');
    }

    public function delete(User $user, ClassSubject $classSubject): bool
    {
        return $user->hasPermission('classes.manage');
    }
}
