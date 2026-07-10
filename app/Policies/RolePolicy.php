<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Role $role): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.manage');
    }

    /**
     * System roles (the 4 SRS actors) are protected from deletion - removing
     * one would break core workflows that assume it exists (SRS §5).
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.manage') && ! $role->is_system;
    }
}
