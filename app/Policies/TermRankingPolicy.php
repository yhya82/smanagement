<?php

namespace App\Policies;

use App\Models\TermRanking;
use App\Models\User;

class TermRankingPolicy
{
    /**
     * Rankings are system-computed (RankingService, Phase 6) - there is
     * deliberately no create/update ability here for end users.
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
}
