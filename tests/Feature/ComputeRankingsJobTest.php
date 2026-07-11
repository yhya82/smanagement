<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Jobs\ComputeRankingsJob;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ComputeRankingsJob::handle() carries the per-class ranking loop that used
 * to run synchronously inside Admin\Rankings\Index::compute() - tested
 * directly here, since that component's own test now only needs to prove
 * the job gets dispatched correctly.
 */
class ComputeRankingsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_job_computes_rankings_for_every_class_with_approved_results_and_notifies_the_requester(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $class->id,
        ]);

        ResultEntry::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
            'term_id' => $term->id, 'score' => 90, 'max_score' => 100, 'status' => ResultStatus::Approved,
            'entered_by' => $teacher->id,
        ]);

        $admin = User::factory()->create(['status' => UserStatus::Active]);

        (new ComputeRankingsJob($term, $admin))->handle(app(RankingService::class));

        $ranking = TermRanking::where('student_id', $student->id)->where('term_id', $term->id)->firstOrFail();
        $this->assertSame(1, $ranking->position);
        $this->assertSame('90.00', $ranking->average);

        $notification = $admin->notifications()->where('type', 'ranking_compute_completed')->firstOrFail();
        $this->assertSame(1, $notification->data['class_count']);
        $this->assertSame(1, $notification->data['ranking_count']);
    }

    public function test_one_classs_failure_does_not_abort_the_others_and_still_notifies_the_requester(): void
    {
        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);

        $classA = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class A']);
        $classB = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Class B']);

        foreach (['A' => $classA, 'B' => $classB] as $label => $class) {
            $studentUser = User::factory()->create(['status' => UserStatus::Active]);
            $student = Student::create([
                'user_id' => $studentUser->id, 'student_no' => "S-{$label}", 'first_name' => $label, 'last_name' => 'Student',
                'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
                'status' => StudentStatus::Active, 'current_class_id' => $class->id,
            ]);

            ResultEntry::create([
                'student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id,
                'term_id' => $term->id, 'score' => 80, 'max_score' => 100, 'status' => ResultStatus::Approved,
                'entered_by' => $teacher->id,
            ]);
        }

        // Simulates class A's compute already being in progress elsewhere
        // (e.g. a concurrent "Compute" click) when this job reaches it -
        // computeForClassTerm() will throw a LockTimeoutException for A
        // specifically, after a few seconds.
        $lock = \Illuminate\Support\Facades\Cache::lock("ranking-compute:{$classA->id}:{$term->id}", 30);
        $this->assertTrue($lock->get());

        $admin = User::factory()->create(['status' => UserStatus::Active]);

        (new ComputeRankingsJob($term, $admin))->handle(app(RankingService::class));

        $this->assertFalse(TermRanking::where('class_id', $classA->id)->exists(), 'Class A should have been skipped, not silently left half-written.');
        $this->assertTrue(TermRanking::where('class_id', $classB->id)->exists(), 'Class B must still be ranked despite Class A failing.');

        $notification = $admin->notifications()->where('type', 'ranking_compute_completed')->firstOrFail();
        $this->assertSame(1, $notification->data['class_count']);
        $this->assertSame(1, $notification->data['failed_count']);
        $this->assertStringContainsString('1 class(es) failed', $notification->message);
    }

    public function test_the_job_does_not_delete_itself_when_a_model_is_missing_and_does_not_auto_retry(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $admin = User::factory()->create(['status' => UserStatus::Active]);

        $job = new ComputeRankingsJob($term, $admin);

        // These two properties are what stop a deleted Term/User from
        // making the job vanish with zero trace, and what stop a job-level
        // retry (which would just redo idempotent work here, but matters a
        // lot more for ImportStudentsJob's sibling implementation).
        $this->assertFalse($job->deleteWhenMissingModels);
        $this->assertSame(1, $job->tries);
    }
}
