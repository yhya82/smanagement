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

/**
 * SRS §15: "Individual enrollment and spreadsheet import (admin only).
 * Spreadsheet columns must match templates and pass validation." Bulk-
 * imported students skip the Registrar-application/document workflow
 * entirely - admin control over the import IS the approval, matching how
 * the SRS frames import as a separate mechanism from individual enrollment,
 * not a bulk version of the application queue.
 */
class StudentImportService
{
    public const EXPECTED_HEADER = [
        'first_name', 'last_name', 'dob', 'gender', 'class_name',
        'guardian_name', 'guardian_relationship', 'guardian_phone',
    ];

    /**
     * @return array{created: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(string $filePath): array
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

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

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
                'class_name' => ['required', 'string'],
                'guardian_name' => ['required', 'string', 'max:255'],
                'guardian_relationship' => ['required', 'string', 'max:255'],
                'guardian_phone' => ['required', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];

                continue;
            }

            $class = SchoolClass::where('name', $data['class_name'])->first();

            if (! $class) {
                $errors[] = ['row' => $rowNumber, 'messages' => ["Class '{$data['class_name']}' not found."]];

                continue;
            }

            $this->createStudent($data, $class);
            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    private function createStudent(array $data, SchoolClass $class): void
    {
        DB::transaction(function () use ($data, $class) {
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

            // No student_application exists for a bulk-imported student -
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
