<?php

namespace App\Policies;

use App\Models\TermRanking;
use App\Models\User;

class TermRankingPolicy
{
    /**
     * Rankings themselves are system-computed (RankingService) - there is
     * deliberately no create ability for end users. update() exists only
     * for the one field a human does write: the term remark, and only the
     * class's own homeroom teacher may write it - not even Administrator,
     * since update/delete are excluded from the Administrator bypass in
     * AppServiceProvider::boot().
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('rankings.view') && ($user->hasRole('Teacher') || $user->student !== null);
    }

    public function view(User $user, TermRanking $ranking): bool
    {
        if ($user->student?->id === $ranking->student_id) {
            return true;
        }

        if (! $user->hasPermission('rankings.view')) {
            return false;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClass($ranking->class_id) ?? false;
        }

        return false;
    }

    public function update(User $user, TermRanking $ranking): bool
    {
        if (! $user->teacher || $ranking->schoolClass->homeroom_teacher_id === null) {
            return false;
        }

        return $user->teacher->id === $ranking->schoolClass->homeroom_teacher_id;
    }
}
