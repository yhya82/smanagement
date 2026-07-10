<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use App\Notifications\ApplicationDecided;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AdmissionService
{
    /**
     * Registrar creates the application + at least one guardian in one
     * transaction (SRS §8, §10). Guardians key off the application, not a
     * student - no student profile exists yet at this point.
     */
    public function createApplication(array $applicant, array $guardians, User $submittedBy): StudentApplication
    {
        if (count($guardians) < 1) {
            throw new InvalidArgumentException('At least one guardian is required.');
        }

        return DB::transaction(function () use ($applicant, $guardians, $submittedBy) {
            $application = StudentApplication::create([
                ...$applicant,
                'submitted_by' => $submittedBy->id,
                'status' => ApprovalStatus::Pending,
            ]);

            foreach ($guardians as $guardian) {
                $application->guardians()->create($guardian);
            }

            $this->notifyApprovers($application);

            return $application->load('guardians');
        });
    }

    /**
     * Validates against the specific document type's own rules (not a
     * single global rule), then either creates the row or - if one already
     * exists for this (application, type) pair - replaces it in place and
     * logs the replacement, per the schema's re-upload/immutability rule.
     */
    public function uploadDocument(
        StudentApplication $application,
        DocumentType $documentType,
        UploadedFile $file,
        User $uploadedBy
    ): ApplicationDocument {
        if ($application->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('Documents can only be uploaded while the application is pending.');
        }

        if (! in_array($file->getMimeType(), $documentType->allowed_mime_types, true)) {
            throw new InvalidArgumentException("'{$file->getClientOriginalName()}' is not an accepted file type for {$documentType->label}.");
        }

        if ($file->getSize() > $documentType->max_file_size_bytes) {
            throw new InvalidArgumentException("'{$file->getClientOriginalName()}' exceeds the maximum size for {$documentType->label}.");
        }

        $path = $file->store("applications/{$application->id}", 'documents');

        $existing = ApplicationDocument::where('student_application_id', $application->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        $attributes = [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'uploaded_by' => $uploadedBy->id,
        ];

        if ($existing) {
            Storage::disk('documents')->delete($existing->file_path);

            AuditLog::create([
                'user_id' => $uploadedBy->id,
                'action' => 'document_replaced',
                'auditable_type' => ApplicationDocument::class,
                'auditable_id' => $existing->id,
                'old_values' => ['file_path' => $existing->file_path],
                'new_values' => ['file_path' => $path],
            ]);

            $existing->update($attributes);

            return $existing;
        }

        return ApplicationDocument::create([
            'student_application_id' => $application->id,
            'document_type_id' => $documentType->id,
            ...$attributes,
        ]);
    }

    /**
     * Collapses the SRS §8 workflow ("profile created -> class assigned ->
     * becomes Active") into one atomic call: the schema has no
     * intermediate "awaiting class assignment" student status, so a class
     * must be supplied up front rather than approving into limbo.
     */
    public function approve(StudentApplication $application, SchoolClass $assignedClass, User $approvedBy): Student
    {
        if ($application->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('Only pending applications can be approved.');
        }

        if (! $application->guardians()->exists()) {
            throw new RuntimeException('This application has no guardian on file.');
        }

        $missingDocumentLabels = DocumentType::where('is_required', true)
            ->whereDoesntHave(
                'applicationDocuments',
                fn ($query) => $query->where('student_application_id', $application->id)
            )
            ->pluck('label');

        if ($missingDocumentLabels->isNotEmpty()) {
            throw new RuntimeException('Missing required documents: '.$missingDocumentLabels->implode(', '));
        }

        return DB::transaction(function () use ($application, $assignedClass, $approvedBy) {
            $studentNo = $this->generateStudentNumber();

            // No personal email is collected at application time (unrealistic
            // to require one from, e.g., a Primary 1 applicant) - a
            // school-issued address is generated instead, matching how real
            // school systems provision student accounts.
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $email = strtolower($studentNo).'@students.'.$host;

            // Active immediately: admin approval is already the deliberate
            // gate (SRS §4/§8) - there's no separate "grant login" action
            // anywhere in the app, so leaving this Inactive would have
            // permanently locked the student out with no way back in.
            $user = User::create([
                'name' => trim("{$application->first_name} {$application->last_name}"),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'status' => UserStatus::Active,
                'must_change_password' => true,
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'student_no' => $studentNo,
                'first_name' => $application->first_name,
                'last_name' => $application->last_name,
                'dob' => $application->dob,
                'gender' => $application->gender,
                'admission_date' => now(),
                'status' => StudentStatus::Active,
                'current_class_id' => $assignedClass->id,
            ]);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $assignedClass->id,
                'academic_year_id' => $assignedClass->academic_year_id,
                'enrollment_date' => now(),
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::Individual,
            ]);

            // Guardian rows collected during application "graduate" to also
            // point at the finished student record, rather than being
            // recreated (schema review §2.5, v4 fix).
            $application->guardians()->update(['student_id' => $student->id]);

            $application->update([
                'status' => ApprovalStatus::Approved,
                'reviewed_by' => $approvedBy->id,
                'reviewed_at' => now(),
                'student_id' => $student->id,
            ]);

            $this->copyPassportPhotoToAvatar($application, $user);

            $application->submittedBy->notify(new ApplicationDecided($application));

            return $student;
        });
    }

    /**
     * Does not touch documents/guardians - the retention job (Phase 8)
     * purges those later, after the 90-day window.
     */
    public function reject(StudentApplication $application, User $rejectedBy, string $reason): StudentApplication
    {
        if ($application->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('Only pending applications can be rejected.');
        }

        $application->update([
            'status' => ApprovalStatus::Rejected,
            'reviewed_by' => $rejectedBy->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $application->submittedBy->notify(new ApplicationDecided($application));

        return $application;
    }

    /**
     * SRS §19 wants applications surfaced to whoever can act on them, not
     * hardcoded to a specific role name - notifies every user holding
     * applications.approve, however that's configured.
     */
    private function notifyApprovers(StudentApplication $application): void
    {
        $approvers = User::whereHas(
            'roles.permissions',
            fn ($query) => $query->where('key', 'applications.approve')
        )->get();

        foreach ($approvers as $approver) {
            $approver->notify(new ApplicationSubmitted($application));
        }
    }

    /**
     * The submitted passport photo stays on the `documents` disk as the
     * permanent, immutable admission record; a copy becomes the live,
     * independently-editable users.profile_picture (schema review §2.4).
     */
    private function copyPassportPhotoToAvatar(StudentApplication $application, User $user): void
    {
        $photoDocument = $application->documents()
            ->whereHas('documentType', fn ($query) => $query->where('key', 'passport_photo'))
            ->first();

        if (! $photoDocument) {
            return;
        }

        $extension = pathinfo($photoDocument->file_path, PATHINFO_EXTENSION);
        $avatarPath = "{$user->id}.{$extension}";

        Storage::disk('avatars')->put($avatarPath, Storage::disk('documents')->get($photoDocument->file_path));

        $user->update(['profile_picture' => $avatarPath]);
    }

    /**
     * NOTE: count()-based numbering isn't fully race-safe under concurrent
     * approvals - acceptable for MVP scale, but a dedicated sequence would
     * be needed before this could be trusted under real concurrent load.
     */
    private function generateStudentNumber(): string
    {
        $year = now()->year;
        $count = Student::whereYear('created_at', $year)->lockForUpdate()->count();

        return sprintf('STU-%d-%04d', $year, $count + 1);
    }
}
