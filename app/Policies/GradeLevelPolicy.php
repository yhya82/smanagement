<?php

namespace App\Policies;

use App\Models\GradeLevel;
use App\Models\User;

class GradeLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GradeLevel $gradeLevel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('grade_levels.manage');
    }

    public function update(User $user, GradeLevel $gradeLevel): bool
    {
        return $user->hasPermission('grade_levels.manage');
    }

    public function delete(User $user, GradeLevel $gradeLevel): bool
    {
        return $user->hasPermission('grade_levels.manage');
    }
}
