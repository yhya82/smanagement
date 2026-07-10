<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * The 4 SRS actors (§3) as system roles - protected from edit/disable
     * (is_system = true) since removing them would break the app's core
     * workflows. Administrator-created custom roles are added later via
     * the UI, not seeded here.
     */
    private const ROLES = [
        'Administrator' => ['all'],
        'Registrar' => [
            'applications.create',
            'students.manage',
            'students.view',
            'enrollment.manage',
        ],
        'Teacher' => [
            'students.view',
            'attendance.mark',
            'attendance.edit',
            'grades.enter',
            'health_records.view.emergency',
            'rankings.view',
        ],
        // Students see only their own records (SRS §20) - an ownership
        // rule enforced by policies, not a capability grant, so no
        // permissions are attached here.
        'Student' => [],
    ];

    public function run(): void
    {
        $allPermissionKeys = Permission::pluck('key');

        foreach (self::ROLES as $name => $permissionKeys) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['is_system' => true, 'is_active' => true]
            );

            $keys = $permissionKeys === ['all'] ? $allPermissionKeys : $permissionKeys;

            $permissionIds = Permission::whereIn('key', $keys)->pluck('id');
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
