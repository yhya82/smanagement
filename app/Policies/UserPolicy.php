<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Covers status changes and password resets - there's no separate
     * "reset password" ability, since both are the same "administrator
     * manages this account" capability (SRS §4/§5).
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage');
    }
}
