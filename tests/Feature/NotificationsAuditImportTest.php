<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Admin\Classes\AddStudent as ClassAddStudent;
use App\Livewire\Admin\Classes\Import as ClassImport;
use App\Livewire\Shared\Notifications;
use App\Livewire\Shared\Profile;
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

    public function test_a_user_can_view_their_own_profile(): void
    {
        $teacherUser = User::factory()->create(['name' => 'Jane Teacher', 'status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        \App\Models\Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T99', 'status' => 'active', 'hire_date' => '2020-01-01']);

        Livewire::actingAs($teacherUser)
            ->test(Profile::class)
            ->assertOk()
            ->assertSee('Jane Teacher')
            ->assertSee('Teacher')
            ->assertSee('T99');
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

    public function test_audit_log_shows_a_readable_before_after_diff_not_raw_json(): void
    {
        AuditLog::create([
            'action' => 'status_changed',
            'auditable_type' => 'Test',
            'auditable_id' => 3,
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
        ]);

        $html = Livewire::actingAs($this->admin)
            ->test(AuditLogsIndex::class)
            ->assertSee('status')
            ->assertSee('pending')
            ->assertSee('approved')
            ->html();

        $this->assertStringNotContainsString('{&quot;status&quot;', $html);
        $this->assertStringNotContainsString('{"status"', $html);
    }

    public function test_admin_can_bulk_import_students_into_a_class(): void
    {
        $class = $this->makeClass();

        $csv = "first_name,last_name,dob,gender,guardian_name,guardian_relationship,guardian_phone\n"
            ."Jane,Doe,2015-05-01,female,John Doe,Father,0551234567\n"
            ."Bad,Row,not-a-date,female,G,Mother,055";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        Livewire::actingAs($this->admin)
            ->test(ClassImport::class, ['class' => $class])
            ->set('file', $file)
            ->call('import');

        $this->assertSame(1, Student::where('first_name', 'Jane')->count());
        $this->assertSame(0, Student::where('first_name', 'Bad')->count());

        $student = Student::where('first_name', 'Jane')->firstOrFail();
        $this->assertSame('active', $student->status->value);
        $this->assertSame('active', $student->user->status->value);
        $this->assertSame($class->id, $student->current_class_id);
        $this->assertTrue(Guardian::where('student_id', $student->id)->where('name', 'John Doe')->exists());
        $this->assertTrue($student->enrollments()->where('source', 'import')->exists());
    }

    public function test_import_rejects_a_csv_with_wrong_columns(): void
    {
        $class = $this->makeClass();
        $file = UploadedFile::fake()->createWithContent('bad.csv', "wrong,columns\nfoo,bar");

        Livewire::actingAs($this->admin)
            ->test(ClassImport::class, ['class' => $class])
            ->set('file', $file)
            ->call('import')
            ->assertSet('importError', fn ($value) => str_contains($value, 'do not match the required template'));
    }

    public function test_import_stops_once_class_capacity_is_reached(): void
    {
        $class = $this->makeClass(capacity: 1);

        $csv = "first_name,last_name,dob,gender,guardian_name,guardian_relationship,guardian_phone\n"
            ."Jane,Doe,2015-05-01,female,John Doe,Father,0551234567\n"
            ."Jack,Doe,2015-05-01,male,John Doe,Father,0551234567";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        Livewire::actingAs($this->admin)
            ->test(ClassImport::class, ['class' => $class])
            ->set('file', $file)
            ->call('import')
            ->assertSet('createdCount', 1);

        $this->assertSame(1, Student::where('first_name', 'Jane')->count());
        $this->assertSame(0, Student::where('first_name', 'Jack')->count());
    }

    public function test_admin_can_enroll_a_single_student_into_a_class(): void
    {
        $class = $this->makeClass();

        Livewire::actingAs($this->admin)
            ->test(ClassAddStudent::class, ['class' => $class])
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('dob', '2015-05-01')
            ->set('gender', 'female')
            ->set('guardian_name', 'John Doe')
            ->set('guardian_relationship', 'Father')
            ->set('guardian_phone', '0551234567')
            ->call('enroll')
            ->assertSet('enrolled', true);

        $student = Student::where('first_name', 'Jane')->firstOrFail();
        $this->assertSame($class->id, $student->current_class_id);
    }

    public function test_cannot_enroll_a_single_student_when_class_is_full(): void
    {
        $class = $this->makeClass(capacity: 1);

        $existingUser = User::create(['name' => 'Existing Student', 'email' => 'existing@test.com', 'password' => 'x', 'status' => UserStatus::Active]);
        Student::create([
            'user_id' => $existingUser->id, 'student_no' => 'S1', 'first_name' => 'Existing', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => \App\Enums\Gender::Male, 'admission_date' => '2024-01-01',
            'status' => \App\Enums\StudentStatus::Active, 'current_class_id' => $class->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ClassAddStudent::class, ['class' => $class])
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('dob', '2015-05-01')
            ->set('gender', 'female')
            ->set('guardian_name', 'John Doe')
            ->set('guardian_relationship', 'Father')
            ->set('guardian_phone', '0551234567')
            ->call('enroll')
            ->assertSet('enrollError', fn ($value) => str_contains($value, 'full capacity'));

        $this->assertSame(0, Student::where('first_name', 'Jane')->count());
    }

    public function test_registrar_cannot_import_students(): void
    {
        $registrar = User::factory()->create(['status' => UserStatus::Active]);
        $registrar->roles()->attach(Role::where('name', 'Registrar')->first());

        $class = $this->makeClass();

        $this->actingAs($registrar)
            ->get(route('admin.classes.import', $class))
            ->assertForbidden();
    }

    private function makeClass(?int $capacity = null): SchoolClass
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);

        return SchoolClass::create([
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $year->id,
            'name' => 'Primary 1',
            'capacity' => $capacity,
        ]);
    }
}
