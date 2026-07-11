<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Jobs\ImportStudentsJob;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ImportStudentsJob::handle() carries the actual import behavior that used
 * to run synchronously inside Admin\Classes\Import::import() - tested here
 * directly rather than through Livewire, since that component's own test
 * now only needs to prove the job gets dispatched correctly.
 */
class ImportStudentsJobTest extends TestCase
{
    use RefreshDatabase;

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

    private function storeCsv(string $csv): string
    {
        Storage::fake('local');

        return UploadedFile::fake()->createWithContent('students.csv', $csv)->store('imports', 'local');
    }

    public function test_the_job_creates_valid_rows_skips_invalid_ones_and_cleans_up_the_file(): void
    {
        $class = $this->makeClass();
        $admin = User::factory()->create(['status' => UserStatus::Active]);

        $csv = "first_name,last_name,dob,gender,guardian_name,guardian_relationship,guardian_phone\n"
            ."Jane,Doe,2015-05-01,female,John Doe,Father,0551234567\n"
            ."Bad,Row,not-a-date,female,G,Mother,055";

        $path = $this->storeCsv($csv);

        (new ImportStudentsJob($class, $path, $admin))->handle(app(StudentImportService::class));

        $this->assertSame(1, Student::where('first_name', 'Jane')->count());
        $this->assertSame(0, Student::where('first_name', 'Bad')->count());

        $student = Student::where('first_name', 'Jane')->firstOrFail();
        $this->assertSame('active', $student->status->value);
        $this->assertSame($class->id, $student->current_class_id);
        $this->assertTrue(Guardian::where('student_id', $student->id)->where('name', 'John Doe')->exists());
        $this->assertTrue($student->enrollments()->where('source', 'import')->exists());

        $this->assertFalse(Storage::disk('local')->exists($path), 'The stored upload should be cleaned up once the job is done with it.');

        $this->assertTrue($admin->notifications()->where('type', 'student_import_completed')->exists());
    }

    public function test_the_job_stops_creating_once_class_capacity_is_reached(): void
    {
        $class = $this->makeClass(capacity: 1);
        $admin = User::factory()->create(['status' => UserStatus::Active]);

        $csv = "first_name,last_name,dob,gender,guardian_name,guardian_relationship,guardian_phone\n"
            ."Jane,Doe,2015-05-01,female,John Doe,Father,0551234567\n"
            ."Jack,Doe,2015-05-01,male,John Doe,Father,0551234567";

        $path = $this->storeCsv($csv);

        (new ImportStudentsJob($class, $path, $admin))->handle(app(StudentImportService::class));

        $this->assertSame(1, Student::where('first_name', 'Jane')->count());
        $this->assertSame(0, Student::where('first_name', 'Jack')->count());

        $notification = $admin->notifications()->where('type', 'student_import_completed')->firstOrFail();
        $this->assertSame(1, $notification->data['created']);
        $this->assertSame(1, $notification->data['failed']);
    }

    public function test_the_file_is_still_cleaned_up_when_the_import_throws(): void
    {
        Storage::fake('local');
        $class = $this->makeClass();
        $admin = User::factory()->create(['status' => UserStatus::Active]);

        // A header that doesn't match the template makes import() throw
        // InvalidArgumentException - the file must still be cleaned up.
        $path = UploadedFile::fake()->createWithContent('bad.csv', "wrong,columns\nfoo,bar")->store('imports', 'local');

        try {
            (new ImportStudentsJob($class, $path, $admin))->handle(app(StudentImportService::class));
            $this->fail('Expected an exception for a mismatched CSV header.');
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertFalse(Storage::disk('local')->exists($path));
    }
}
