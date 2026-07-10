<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('promotions.approve') || $user->hasRole('Teacher') || $user->student !== null;
    }

    public function view(User $user, Promotion $promotion): bool
    {
        if ($user->student?->id === $promotion->student_id) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClass($promotion->from_class_id) ?? false;
        }

        return $user->hasPermission('promotions.approve');
    }

    /**
     * Promotion is for active students only and always requires
     * administrator approval (SRS §18) - only the one permission governs
     * both requesting and approving here, matching current role mappings.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function approve(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return false;
    }
}
