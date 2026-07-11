<?php

namespace Tests\Unit;

use App\Enums\ExamType;
use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
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
 * Isolated tests of RankingService's own ranking math (standard
 * competition ranking: tied scores share a position, the next distinct
 * value skips by the tied count) - this exact behavior wasn't covered by
 * any existing test, Feature-level or otherwise, before this file.
 */
class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;

    private Term $term;

    private Subject $subject;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $this->teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);
    }

    private function makeStudentWithScore(string $name, float $score): Student
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $student = Student::create([
            'user_id' => $user->id, 'student_no' => "S-{$name}", 'first_name' => $name, 'last_name' => 'Test',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $this->class->id,
        ]);

        ResultEntry::create([
            'student_id' => $student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Final, 'score' => $score, 'max_score' => 100,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);

        return $student;
    }

    public function test_two_tied_students_share_position_one_and_the_next_skips_to_three(): void
    {
        $a = $this->makeStudentWithScore('A', 90);
        $b = $this->makeStudentWithScore('B', 90);
        $c = $this->makeStudentWithScore('C', 80);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        $rankingA = TermRanking::where('student_id', $a->id)->firstOrFail();
        $rankingB = TermRanking::where('student_id', $b->id)->firstOrFail();
        $rankingC = TermRanking::where('student_id', $c->id)->firstOrFail();

        $this->assertSame(1, $rankingA->position);
        $this->assertSame(1, $rankingB->position);
        $this->assertTrue($rankingA->is_tied);
        $this->assertTrue($rankingB->is_tied);

        $this->assertSame(3, $rankingC->position, 'Position after a 2-way tie for 1st must skip to 3rd, not 2nd.');
        $this->assertFalse($rankingC->is_tied);
    }

    public function test_three_tied_students_share_position_one_and_the_fourth_is_position_four(): void
    {
        $a = $this->makeStudentWithScore('A', 70);
        $b = $this->makeStudentWithScore('B', 70);
        $c = $this->makeStudentWithScore('C', 70);
        $d = $this->makeStudentWithScore('D', 60);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        foreach ([$a, $b, $c] as $tiedStudent) {
            $ranking = TermRanking::where('student_id', $tiedStudent->id)->firstOrFail();
            $this->assertSame(1, $ranking->position);
            $this->assertTrue($ranking->is_tied);
        }

        $rankingD = TermRanking::where('student_id', $d->id)->firstOrFail();
        $this->assertSame(4, $rankingD->position);
        $this->assertFalse($rankingD->is_tied);
    }

    public function test_no_ties_produces_plain_sequential_positions(): void
    {
        $a = $this->makeStudentWithScore('A', 95);
        $b = $this->makeStudentWithScore('B', 85);
        $c = $this->makeStudentWithScore('C', 75);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        $this->assertSame(1, TermRanking::where('student_id', $a->id)->firstOrFail()->position);
        $this->assertSame(2, TermRanking::where('student_id', $b->id)->firstOrFail()->position);
        $this->assertSame(3, TermRanking::where('student_id', $c->id)->firstOrFail()->position);
    }
}
