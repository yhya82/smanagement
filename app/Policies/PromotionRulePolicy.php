<?php

namespace App\Policies;

use App\Models\PromotionRule;
use App\Models\User;

class PromotionRulePolicy
{
    /**
     * Reuses promotions.approve rather than a separate permission key:
     * only Administrator deals with promotion configuration at all per SRS
     * §18, so a dedicated "promotion_rules.manage" key would be a distinction
     * without a difference given current role mappings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function view(User $user, PromotionRule $rule): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function update(User $user, PromotionRule $rule): bool
    {
        return $user->hasPermission('promotions.approve');
    }

    public function delete(User $user, PromotionRule $rule): bool
    {
        return $user->hasPermission('promotions.approve');
    }
}
