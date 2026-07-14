<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    public function updated(User $user): void
    {
        // SRS §11/§21 only names health changes as an explicitly mandatory
        // audit trail - profile picture changes aren't named at all. Reusing
        // the generic audit_logs mechanism here is a deliberate inclusion
        // (schema review §2.1), not an oversight.
        if ($user->wasChanged('profile_picture')) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'profile_picture_updated',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => ['profile_picture' => $user->getOriginal('profile_picture')],
                'new_values' => ['profile_picture' => $user->profile_picture],
                'ip_address' => request()?->ip(),
            ]);
        }

        // SRS §21 explicitly names "password changes" - never log the
        // actual hash, just that a change happened.
        if ($user->wasChanged('password')) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'password_changed',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => request()?->ip(),
            ]);
        }
    }
}
