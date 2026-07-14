<?php

namespace App\Services;

use App\Enums\TeacherStatus;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * SRS §12: teachers may be onboarded directly by an administrator. Mirrors
 * AdmissionService's user+profile creation pattern (active immediately,
 * forced password change, generated identifier) rather than introducing a
 * second convention for the same kind of atomic account-creation step.
 */
class TeacherOnboardingService
{
    public function onboard(string $name, string $email, string $hireDate): Teacher
    {
        return DB::transaction(function () use ($name, $email, $hireDate) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'status' => UserStatus::Active,
                'must_change_password' => true,
            ]);

            $user->roles()->attach(Role::where('name', 'Teacher')->firstOrFail());

            return Teacher::create([
                'user_id' => $user->id,
                'employee_no' => $this->generateEmployeeNumber(),
                'status' => TeacherStatus::Pending,
                'hire_date' => $hireDate,
            ]);
        });
    }

    /**
     * NOTE: count()-based numbering isn't fully race-safe under concurrent
     * onboarding - same acceptable-for-MVP tradeoff as
     * AdmissionService::generateStudentNumber().
     */
    private function generateEmployeeNumber(): string
    {
        $year = now()->year;
        $count = Teacher::whereYear('created_at', $year)->lockForUpdate()->count();

        return sprintf('EMP-%d-%04d', $year, $count + 1);
    }
}
