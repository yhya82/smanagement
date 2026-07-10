<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\ApplicationReviewShow;
use App\Livewire\Registrar\ApplicationCreate;
use App\Livewire\Registrar\ApplicationShow;
use App\Models\AcademicYear;
use App\Models\DocumentType;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\StudentApplication;
use App\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exercises the exact slice verified in Phase 11: registrar creates an
 * application, uploads both required documents, and admin approves it into
 * a class - the same flow a browser session would drive, through
 * Livewire::test() rather than a real browser (no headless Chromium
 * available in this environment - the download stalled with no network
 * progress after 8+ minutes).
 */
class ApplicationIntakeFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;

    private User $admin;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(GradeLevelSeeder::class);
        $this->seed(AcademicYearSeeder::class);

        $this->registrar = User::factory()->create(['status' => UserStatus::Active]);
        $this->registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $this->class = SchoolClass::create([
            'grade_level_id' => GradeLevel::first()->id,
            'academic_year_id' => AcademicYear::first()->id,
            'name' => 'Test Class',
        ]);

        Storage::fake('documents');
        Storage::fake('avatars');
    }

    public function test_full_intake_and_approval_flow(): void
    {
        // --- Registrar creates the application ---
        $application = Livewire::actingAs($this->registrar)
            ->test(ApplicationCreate::class)
            ->set('first_name', 'Kofi')
            ->set('last_name', 'Mensah')
            ->set('dob', '2015-05-01')
            ->set('gender', 'male')
            ->set('guardians.0.name', 'Ama Mensah')
            ->set('guardians.0.relationship', 'Mother')
            ->set('guardians.0.phone', '0551234567')
            ->call('save')
            ->assertHasNoErrors();

        $studentApplication = StudentApplication::where('first_name', 'Kofi')->firstOrFail();
        $this->assertSame(1, $studentApplication->guardians()->count());

        // --- Registrar uploads both required documents ---
        $birthCertType = DocumentType::where('key', 'birth_certificate')->first();
        $passportPhotoType = DocumentType::where('key', 'passport_photo')->first();

        $show = Livewire::actingAs($this->registrar)
            ->test(ApplicationShow::class, ['application' => $studentApplication]);

        $show->set("uploads.{$birthCertType->id}", UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'))
            ->call('uploadDocument', $birthCertType->id)
            ->assertHasNoErrors();

        $show->set("uploads.{$passportPhotoType->id}", UploadedFile::fake()->image('photo.jpg'))
            ->call('uploadDocument', $passportPhotoType->id)
            ->assertHasNoErrors();

        $this->assertSame(2, $studentApplication->documents()->count());

        // --- Admin reviews and approves ---
        $review = Livewire::actingAs($this->admin)
            ->test(ApplicationReviewShow::class, ['application' => $studentApplication->fresh()]);

        $this->assertEmpty($review->missingDocumentLabels);

        $review->set('class_id', (string) $this->class->id)
            ->call('approve')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.applications.index'));

        $studentApplication->refresh();
        $this->assertSame('approved', $studentApplication->status->value);
        $this->assertNotNull($studentApplication->student_id);

        $student = $studentApplication->student;
        $this->assertSame($this->class->id, $student->current_class_id);
        $this->assertSame('active', $student->status->value);
        $this->assertNotNull($student->user->profile_picture);
        $this->assertTrue($this->registrar->notifications()->where('type', 'application_decided')->exists());

        // The student's user account must actually be able to log in -
        // nothing in the app ever flips a user from inactive to active, so
        // creating it inactive here would have permanently locked them out.
        $this->assertSame('active', $student->user->status->value);
    }

    public function test_approval_is_blocked_when_documents_are_missing(): void
    {
        $application = Livewire::actingAs($this->registrar)
            ->test(ApplicationCreate::class)
            ->set('first_name', 'NoDocuments')
            ->set('last_name', 'Test')
            ->set('dob', '2015-05-01')
            ->set('gender', 'female')
            ->set('guardians.0.name', 'G')
            ->set('guardians.0.relationship', 'Father')
            ->set('guardians.0.phone', '055')
            ->call('save');

        $studentApplication = StudentApplication::where('first_name', 'NoDocuments')->firstOrFail();

        $review = Livewire::actingAs($this->admin)
            ->test(ApplicationReviewShow::class, ['application' => $studentApplication]);

        $this->assertCount(2, $review->missingDocumentLabels);

        $review->set('class_id', (string) $this->class->id)->call('approve');

        $this->assertSame('pending', $studentApplication->fresh()->status->value);
        $review->assertSet('decisionError', fn ($value) => str_contains($value, 'Missing required documents'));
    }
}
