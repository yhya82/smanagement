<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Jobs\LockAttendanceRecordsJob;
use App\Jobs\PurgeRejectedApplicationDocumentsJob;
use App\Models\AcademicYear;
use App\Models\ApplicationDocument;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 12 job tests: Carbon time travel (Carbon::setTestNow), not real
 * sleeps - both jobs key their behavior off now(), so freezing time lets
 * fixtures be planted on either side of the retention boundary and the
 * exact boundary itself, deterministically.
 */
class ScheduledJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attendance_lock_job_locks_records_past_the_7_day_window_and_leaves_recent_ones_open(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);

        $studentUser = User::create(['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01', 'current_class_id' => $class->id,
        ]);

        $teacherUser = User::create(['name' => 'Test Teacher', 'email' => 'teacher@test.com', 'password' => 'x', 'status' => UserStatus::Active, 'must_change_password' => false]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $recordEightDaysOld = AttendanceRecord::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-07-02',
            'status' => AttendanceStatus::Present, 'marked_by' => $teacher->id, 'marked_at' => now(),
        ]);
        $recordExactlySevenDaysOld = AttendanceRecord::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-07-03',
            'status' => AttendanceStatus::Present, 'marked_by' => $teacher->id, 'marked_at' => now(),
        ]);
        $recordThreeDaysOld = AttendanceRecord::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'date' => '2026-07-07',
            'status' => AttendanceStatus::Present, 'marked_by' => $teacher->id, 'marked_at' => now(),
        ]);

        (new LockAttendanceRecordsJob)->handle();

        $this->assertNotNull($recordEightDaysOld->fresh()->locked_at, 'a record past the 7-day window must be locked');
        $this->assertNotNull($recordExactlySevenDaysOld->fresh()->locked_at, 'the boundary day itself must be locked (inclusive)');
        $this->assertNull($recordThreeDaysOld->fresh()->locked_at, 'a record still within the window must stay unlocked');
    }

    public function test_document_purge_job_only_purges_rejected_applications_past_90_days(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');

        Storage::fake('documents');

        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $documentType = DocumentType::create([
            'key' => 'birth_certificate', 'label' => 'Birth Certificate', 'is_required' => true, 'is_active' => true,
            'allowed_mime_types' => ['application/pdf'], 'max_file_size_bytes' => 5 * 1024 * 1024,
        ]);

        $overdue = $this->makeApplicationWithDocumentsAndGuardian('rejected', now()->subDays(91), $documentType, $registrar);
        $notYetDue = $this->makeApplicationWithDocumentsAndGuardian('rejected', now()->subDays(89), $documentType, $registrar);
        $approved = $this->makeApplicationWithDocumentsAndGuardian('approved', now()->subDays(200), $documentType, $registrar);
        $alreadyPurged = $this->makeApplicationWithDocumentsAndGuardian('rejected', now()->subDays(100), $documentType, $registrar);
        $alreadyPurged->update(['documents_purged_at' => now()->subDay()]);
        // Simulate its documents already having been removed on the earlier purge run.
        $alreadyPurged->documents()->delete();
        $alreadyPurged->guardians()->delete();

        (new PurgeRejectedApplicationDocumentsJob)->handle();

        $this->assertSame(0, $overdue->documents()->count());
        $this->assertSame(0, $overdue->guardians()->count());
        $this->assertNotNull($overdue->fresh()->documents_purged_at);
        $this->assertTrue(AuditLog::where('action', 'documents_purged')->where('auditable_id', $overdue->id)->exists());

        $this->assertSame(1, $notYetDue->documents()->count(), 'rejected but still within the 90-day window must be untouched');
        $this->assertNull($notYetDue->fresh()->documents_purged_at);

        $this->assertSame(1, $approved->documents()->count(), 'approved applications must never be purged regardless of age');
        $this->assertNull($approved->fresh()->documents_purged_at);

        $this->assertSame(1, AuditLog::where('action', 'documents_purged')->count(), 'the already-purged application must not be reprocessed');
    }

    private function makeApplicationWithDocumentsAndGuardian(string $status, $reviewedAt, DocumentType $documentType, User $registrar): StudentApplication
    {
        $application = StudentApplication::create([
            'first_name' => 'Test', 'last_name' => 'Applicant', 'dob' => '2015-01-01', 'gender' => 'male',
            'submitted_by' => $registrar->id, 'status' => $status, 'reviewed_by' => $registrar->id, 'reviewed_at' => $reviewedAt,
        ]);

        $path = "applications/{$application->id}/cert.pdf";
        Storage::disk('documents')->put($path, 'fake contents');

        ApplicationDocument::create([
            'student_application_id' => $application->id, 'document_type_id' => $documentType->id,
            'file_path' => $path, 'original_filename' => 'cert.pdf', 'mime_type' => 'application/pdf',
            'file_size_bytes' => 13, 'uploaded_by' => $registrar->id,
        ]);

        Guardian::create([
            'student_application_id' => $application->id, 'name' => 'Test Guardian',
            'relationship' => 'Mother', 'phone' => '0551234567', 'is_primary' => true,
        ]);

        return $application;
    }
}
