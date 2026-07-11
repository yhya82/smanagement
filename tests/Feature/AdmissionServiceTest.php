<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\DocumentType;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\StudentApplication;
use App\Models\User;
use App\Services\AdmissionService;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 12: feature tests against the service directly rather than through
 * a controller/Livewire component - AdmissionService::approve() is the
 * most complex transaction in the app (SRS §8), so its invariants are
 * tested here independent of whatever UI happens to call it.
 */
class AdmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdmissionService $admissionService;

    private User $registrar;

    private User $admin;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(DocumentTypeSeeder::class);

        $this->admissionService = app(AdmissionService::class);

        $this->registrar = User::factory()->create(['status' => UserStatus::Active]);
        $this->registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);

        Storage::fake('documents');
        Storage::fake('avatars');
    }

    public function test_approval_is_blocked_when_the_application_has_no_guardian(): void
    {
        // Created directly, bypassing ApplicationCreate's own guardian
        // requirement - proves the service enforces this itself rather than
        // relying entirely on the UI layer.
        $application = StudentApplication::create([
            'first_name' => 'No', 'last_name' => 'Guardian', 'dob' => '2015-01-01',
            'gender' => 'male', 'submitted_by' => $this->registrar->id, 'status' => 'pending',
        ]);

        $this->uploadRequiredDocuments($application);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no guardian on file');

        $this->admissionService->approve($application->fresh(), $this->class, $this->admin);
    }

    public function test_approval_backfills_student_id_onto_the_application_and_its_guardians(): void
    {
        $application = StudentApplication::create([
            'first_name' => 'Kofi', 'last_name' => 'Mensah', 'dob' => '2015-05-01',
            'gender' => 'male', 'submitted_by' => $this->registrar->id, 'status' => 'pending',
        ]);
        $application->guardians()->create(['name' => 'Ama Mensah', 'relationship' => 'Mother', 'phone' => '0551234567', 'is_primary' => true]);
        $application->guardians()->create(['name' => 'Kwame Mensah', 'relationship' => 'Father', 'phone' => '0557654321', 'is_primary' => false]);

        $this->uploadRequiredDocuments($application);

        $student = $this->admissionService->approve($application->fresh(), $this->class, $this->admin);

        $this->assertSame($student->id, $application->fresh()->student_id);
        $this->assertSame(2, $application->guardians()->where('student_id', $student->id)->count());
    }

    private function uploadRequiredDocuments(StudentApplication $application): void
    {
        $birthCertType = DocumentType::where('key', 'birth_certificate')->firstOrFail();
        $passportPhotoType = DocumentType::where('key', 'passport_photo')->firstOrFail();

        $this->admissionService->uploadDocument(
            $application, $birthCertType, UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'), $this->registrar
        );
        $this->admissionService->uploadDocument(
            $application, $passportPhotoType, UploadedFile::fake()->image('photo.jpg'), $this->registrar
        );
    }
}
