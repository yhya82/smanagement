<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Admin\Students\Import as StudentsImport;
use App\Livewire\Shared\Notifications;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsAuditImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());
    }

    public function test_a_registrar_can_view_and_mark_read_their_own_notifications(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $notification = $registrar->notifications()->create([
            'type' => 'test', 'title' => 'Test', 'message' => 'Hello',
        ]);

        Livewire::actingAs($registrar)
            ->test(Notifications::class)
            ->assertSee('Test')
            ->call('markRead', $notification->id);

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_a_user_cannot_mark_another_users_notification_read(): void
    {
        $userA = User::factory()->create(['status' => UserStatus::Active]);
        $userB = User::factory()->create(['status' => UserStatus::Active]);
        $userA->roles()->attach(Role::where('name', 'Registrar')->first());
        $userB->roles()->attach(Role::where('name', 'Registrar')->first());

        $notification = $userB->notifications()->create(['type' => 't', 'title' => 'T', 'message' => 'M']);

        Livewire::actingAs($userA)
            ->test(Notifications::class)
            ->call('markRead', $notification->id)
            ->assertForbidden();
    }

    public function test_admin_can_view_and_filter_the_audit_log(): void
    {
        AuditLog::create(['action' => 'test_action', 'auditable_type' => 'Test', 'auditable_id' => 1]);
        AuditLog::create(['action' => 'other_action', 'auditable_type' => 'Test', 'auditable_id' => 2]);

        // "other_action" itself always appears (it's listed as a <select>
        // filter option regardless of which one is active) - check the
        // record identifier instead, which only appears in a matching row.
        Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSee('Test #1')
            ->assertSee('Test #2')
            ->set('action', 'test_action')
            ->assertSee('Test #1')
            ->assertDontSee('Test #2');
    }

    public function test_admin_can_bulk_import_students_from_csv(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Primary 1']);

        $csv = "first_name,last_name,dob,gender,class_name,guardian_name,guardian_relationship,guardian_phone\n"
            ."Jane,Doe,2015-05-01,female,Primary 1,John Doe,Father,0551234567\n"
            ."Bad,Row,not-a-date,female,Primary 1,G,Mother,055\n"
            ."Nowhere,Kid,2015-05-01,male,Nonexistent Class,G2,Mother,055";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        Livewire::actingAs($this->admin)
            ->test(StudentsImport::class)
            ->set('file', $file)
            ->call('import');

        $this->assertSame(1, Student::where('first_name', 'Jane')->count());
        $this->assertSame(0, Student::where('first_name', 'Bad')->count());
        $this->assertSame(0, Student::where('first_name', 'Nowhere')->count());

        $student = Student::where('first_name', 'Jane')->firstOrFail();
        $this->assertSame('active', $student->status->value);
        $this->assertSame('active', $student->user->status->value);
        $this->assertTrue(Guardian::where('student_id', $student->id)->where('name', 'John Doe')->exists());
        $this->assertTrue($student->enrollments()->where('source', 'import')->exists());
    }

    public function test_import_rejects_a_csv_with_wrong_columns(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.csv', "wrong,columns\nfoo,bar");

        Livewire::actingAs($this->admin)
            ->test(StudentsImport::class)
            ->set('file', $file)
            ->call('import')
            ->assertSet('importError', fn ($value) => str_contains($value, 'do not match the required template'));
    }

    public function test_registrar_cannot_import_students(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $this->actingAs($registrar)
            ->get(route('admin.students.import'))
            ->assertForbidden();
    }
}
