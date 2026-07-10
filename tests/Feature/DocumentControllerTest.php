<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\ApplicationDocument;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\StudentApplication;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocument(): ApplicationDocument
    {
        Storage::fake('documents');

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $application = StudentApplication::create([
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'dob' => '2015-01-01',
            'gender' => Gender::Male,
            'submitted_by' => $registrar->id,
            'status' => ApprovalStatus::Pending,
        ]);

        $documentType = DocumentType::create([
            'key' => 'birth_certificate',
            'label' => 'Birth Certificate',
            'is_required' => true,
            'allowed_mime_types' => ['application/pdf'],
            'max_file_size_bytes' => 5 * 1024 * 1024,
        ]);

        $file = UploadedFile::fake()->create('cert.pdf', 10, 'application/pdf');
        $path = $file->store("applications/{$application->id}", 'documents');

        return ApplicationDocument::create([
            'student_application_id' => $application->id,
            'document_type_id' => $documentType->id,
            'file_path' => $path,
            'original_filename' => 'cert.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 10240,
            'uploaded_by' => $registrar->id,
        ]);
    }

    public function test_guest_cannot_access_a_document(): void
    {
        $document = $this->makeDocument();

        $this->get(route('application-documents.stream', $document))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_admission_permissions_is_forbidden(): void
    {
        $document = $this->makeDocument();

        $student = User::factory()->create(['status' => UserStatus::Active]);
        $student->roles()->attach(Role::where('name', 'Student')->first());

        $this->actingAs($student)
            ->get(route('application-documents.stream', $document))
            ->assertForbidden();
    }

    public function test_registrar_can_stream_the_document(): void
    {
        $document = $this->makeDocument();

        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('application-documents.stream', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
