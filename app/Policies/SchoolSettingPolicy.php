<?php

namespace App\Policies;

use App\Models\SchoolSetting;
use App\Models\User;

/**
 * No create/delete: the single row is seeded once and only ever updated.
 * view() isn't actually gated anywhere - the school name/logo are public
 * branding shown on the login page to guests - this exists for
 * completeness/symmetry with every other admin-config policy, not because
 * anything calls $this->authorize('view', ...) on it.
 */
class SchoolSettingPolicy
{
    public function view(User $user, SchoolSetting $schoolSetting): bool
    {
        return true;
    }

    public function update(User $user, SchoolSetting $schoolSetting): bool
    {
        return $user->hasPermission('settings.manage');
    }
}
