<?php

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * SRS §15: "Individual enrollment and spreadsheet import (admin only)."
 * Both live on the class page, scoped to one class at a time, so both the
 * single-student form and each row of a bulk import funnel through
 * enrollStudent() to share creation logic and the capacity check. Bulk-
 * imported/individually-enrolled students skip the Registrar-application/
 * document workflow entirely - admin control over the action IS the
 * approval, matching how the SRS frames these as separate from the
 * application queue.
 */
class StudentImportService
{
    public const EXPECTED_HEADER = [
        'first_name', 'last_name', 'dob', 'gender',
        'guardian_name', 'guardian_relationship', 'guardian_phone',
    ];

    /**
     * The target class is fixed by the page the admin is on, not a per-row
     * CSV column - every row in the file enrolls into $class.
     *
     * @return array{created: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(string $filePath, SchoolClass $class): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);

        if ($header !== self::EXPECTED_HEADER) {
            fclose($handle);

            throw new InvalidArgumentException(
                'CSV columns do not match the required template: '.implode(', ', self::EXPECTED_HEADER)
            );
        }

        $created = 0;
        $errors = [];
        $rowNumber = 1;
        $capacityReached = false;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($capacityReached) {
                $errors[] = ['row' => $rowNumber, 'messages' => ["Skipped: '{$class->name}' reached full capacity."]];

                continue;
            }

            if (count($row) !== count(self::EXPECTED_HEADER)) {
                $errors[] = ['row' => $rowNumber, 'messages' => ['Wrong number of columns.']];

                continue;
            }

            $data = array_combine(self::EXPECTED_HEADER, $row);

            $validator = Validator::make($data, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'dob' => ['required', 'date'],
                'gender' => ['required', 'in:male,female,other'],
                'guardian_name' => ['required', 'string', 'max:255'],
                'guardian_relationship' => ['required', 'string', 'max:255'],
                'guardian_phone' => ['required', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];

                continue;
            }

            try {
                $this->enrollStudent($data, $class);
                $created++;
            } catch (RuntimeException $e) {
                $errors[] = ['row' => $rowNumber, 'messages' => [$e->getMessage()]];
                $capacityReached = true;
            }
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Creates one student, one primary guardian, and an active enrollment
     * directly into $class - used for both single "add student" submissions
     * and each row of a bulk import. Skips the application/document
     * workflow entirely (SRS §15's "individual enrollment" path).
     */
    public function enrollStudent(array $data, SchoolClass $class): Student
    {
        return DB::transaction(function () use ($data, $class) {
            if (! $class->hasCapacityFor()) {
                throw new RuntimeException("'{$class->name}' is at full capacity.");
            }

            $studentNo = $this->generateStudentNumber();

            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $email = strtolower($studentNo).'@students.'.$host;

            $user = User::create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'status' => UserStatus::Active,
                'must_change_password' => true,
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'student_no' => $studentNo,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'dob' => $data['dob'],
                'gender' => Gender::from($data['gender']),
                'admission_date' => now(),
                'status' => StudentStatus::Active,
                'current_class_id' => $class->id,
            ]);

            // No student_application exists for a directly-enrolled student -
            // the guardian attaches directly to the student instead (schema
            // migration 2026_07_10_130001 made student_application_id
            // nullable for exactly this case).
            Guardian::create([
                'student_id' => $student->id,
                'name' => $data['guardian_name'],
                'relationship' => $data['guardian_relationship'],
                'phone' => $data['guardian_phone'],
                'is_primary' => true,
            ]);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $class->academic_year_id,
                'enrollment_date' => now(),
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::Import,
            ]);

            return $student;
        });
    }

    /**
     * NOTE: same count()-based numbering tradeoff as
     * AdmissionService::generateStudentNumber() - not fully race-safe under
     * concurrent imports, acceptable for MVP scale.
     */
    private function generateStudentNumber(): string
    {
        $year = now()->year;
        $count = Student::whereYear('created_at', $year)->lockForUpdate()->count();

        return sprintf('STU-%d-%04d', $year, $count + 1);
    }
}
