<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * One row per capability referenced in the SRS. Data-access scoping
     * (e.g. "teachers only see assigned classes", "students only see their
     * own records") is enforced by policies against the relevant tables
     * (teacher_subject_assignments, ownership), not by separate permission
     * keys - so there's deliberately no "students.view.scoped" etc.
     */
    private const PERMISSIONS = [
        // Administrator core (SRS §5, §6)
        ['key' => 'users.manage', 'description' => 'Create, edit, and reset passwords for user accounts'],
        ['key' => 'roles.manage', 'description' => 'Create, edit, and disable roles'],
        ['key' => 'permissions.manage', 'description' => 'Assign permissions to roles'],
        ['key' => 'settings.manage', 'description' => 'Manage system configuration'],
        ['key' => 'academic_years.manage', 'description' => 'Create and activate academic years'],
        ['key' => 'terms.manage', 'description' => 'Create and activate terms'],
        ['key' => 'grade_levels.manage', 'description' => 'Manage grade level structure'],
        ['key' => 'classes.manage', 'description' => 'Create and edit classes'],
        ['key' => 'subjects.manage', 'description' => 'Create and edit subjects'],
        ['key' => 'teacher_assignments.manage', 'description' => 'Assign teachers to subjects and classes'],
        ['key' => 'document_types.manage', 'description' => 'Configure required admission document types'],

        // Admission (SRS §7, §8)
        ['key' => 'applications.create', 'description' => 'Create and edit student applications'],
        ['key' => 'applications.approve', 'description' => 'Approve or reject student applications'],

        // Students (SRS §7, §9)
        ['key' => 'students.view', 'description' => 'View student profiles and records'],
        ['key' => 'students.manage', 'description' => 'Manage student records, transfers, and withdrawals'],

        // Health records (SRS §11)
        ['key' => 'health_records.view.emergency', 'description' => 'View emergency health info for assigned students'],
        ['key' => 'health_records.view.full', 'description' => 'View full medical records'],
        ['key' => 'health_records.manage', 'description' => 'Edit student health records'],

        // Enrollment (SRS §15)
        ['key' => 'enrollment.manage', 'description' => 'Enroll students individually'],
        ['key' => 'enrollment.import', 'description' => 'Bulk-enroll students via spreadsheet import'],

        // Attendance (SRS §16)
        ['key' => 'attendance.mark', 'description' => 'Mark individual or bulk attendance'],
        ['key' => 'attendance.edit', 'description' => 'Edit attendance within the 7-day window'],
        ['key' => 'attendance.edit.approve', 'description' => 'Approve attendance edits after the 7-day window'],

        // Grades & results (SRS §17)
        ['key' => 'grades.enter', 'description' => 'Enter grades for assigned subjects'],
        ['key' => 'grades.approve', 'description' => 'Review and approve entered grades'],

        // Ranking & promotion (SRS §18)
        ['key' => 'rankings.view', 'description' => 'View term rankings'],
        ['key' => 'promotions.approve', 'description' => 'Approve student promotions and class movement'],

        // Audit (SRS §21)
        ['key' => 'audit.view', 'description' => 'View audit and history logs'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['key' => $permission['key']], $permission);
        }
    }
}
