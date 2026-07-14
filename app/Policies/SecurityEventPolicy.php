<?php

namespace App\Policies;

use App\Models\SecurityEvent;
use App\Models\User;

class SecurityEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, SecurityEvent $securityEvent): bool
    {
        return $user->hasPermission('audit.view');
    }

    /**
     * Immutable append-only trail, same reasoning as AuditLogPolicy - the
     * Administrator Gate::before bypass explicitly excludes update/delete,
     * so this hardcoded false is actually reached rather than waved through.
     */
    public function update(User $user, SecurityEvent $securityEvent): bool
    {
        return false;
    }

    public function delete(User $user, SecurityEvent $securityEvent): bool
    {
        return false;
    }
}
