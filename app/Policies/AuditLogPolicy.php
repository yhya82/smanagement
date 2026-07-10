<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermission('audit.view');
    }

    /**
     * Audit logs are an immutable trail (SRS §21) - never editable or
     * deletable by anyone. The Administrator Gate::before bypass explicitly
     * excludes update/delete (see AppServiceProvider), so this hardcoded
     * false is actually reached rather than being waved through.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
