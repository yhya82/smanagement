<?php

namespace Tests\Feature;

use App\Enums\ExamType;
use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\Settings\Edit as SettingsEdit;
use App\Livewire\Student\Results as StudentResults;
use App\Livewire\Teacher\Grades as TeacherGrades;
use App\Livewire\Teacher\Remarks as TeacherRemarks;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\GradeLevel;
use App\Models\ResultEntry;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TermRanking;
use App\Models\User;
use App\Services\RankingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamResultsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolClass $class;

    private Term $term;

    private Subject $subject;

    private Teacher $teacher;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->roles()->attach(Role::where('name', 'Administrator')->first());

        $gradeLevel = GradeLevel::create(['name' => 'Primary 1', 'sort_order' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'is_active' => true]);
        $this->term = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-12', 'is_active' => true]);
        $this->class = SchoolClass::create(['grade_level_id' => $gradeLevel->id, 'academic_year_id' => $year->id, 'name' => 'Blue Stream']);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH1']);
        ClassSubject::create(['class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'term_id' => $this->term->id]);

        $teacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $teacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        $this->teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'T1', 'status' => 'active', 'hire_date' => '2020-01-01']);
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'class_id' => $this->class->id, 'term_id' => $this->term->id, 'is_active' => true,
        ]);

        $studentUser = User::factory()->create(['status' => UserStatus::Active]);
        $studentUser->roles()->attach(Role::where('name', 'Student')->first());
        $this->student = Student::create([
            'user_id' => $studentUser->id, 'student_no' => 'S1', 'first_name' => 'Test', 'last_name' => 'Student',
            'dob' => '2015-01-01', 'gender' => Gender::Male, 'admission_date' => '2024-01-01',
            'status' => StudentStatus::Active, 'current_class_id' => $this->class->id,
        ]);
    }

    public function test_teacher_can_save_and_submit_midterm_and_final_independently(): void
    {
        $component = Livewire::actingAs($this->teacher->user)
            ->test(TeacherGrades::class, ['class' => $this->class, 'subject' => $this->subject]);

        $component->set("entries.{$this->student->id}.midterm.score", '30')
            ->set("entries.{$this->student->id}.midterm.max_score", '40')
            ->call('saveDrafts');

        $this->assertSame(1, ResultEntry::where('exam_type', ExamType::Midterm)->count());
        $this->assertSame(0, ResultEntry::where('exam_type', ExamType::Final)->count());

        $component->call('submitMidterm');
        $this->assertTrue(ResultEntry::where('exam_type', ExamType::Midterm)->where('status', ResultStatus::Submitted)->exists());

        // Final scores are entered later in the term - midterm being
        // submitted must not block a fresh final draft.
        $component->set("entries.{$this->student->id}.final.score", '55')
            ->set("entries.{$this->student->id}.final.max_score", '60')
            ->call('saveDrafts');

        $this->assertSame(1, ResultEntry::where('exam_type', ExamType::Final)->where('status', ResultStatus::Draft)->count());

        $component->call('submitFinal');
        $this->assertTrue(ResultEntry::where('exam_type', ExamType::Final)->where('status', ResultStatus::Submitted)->exists());
    }

    public function test_ranking_combines_midterm_and_final_using_configured_weights(): void
    {
        SchoolSetting::current()->update(['midterm_weight' => 40, 'final_weight' => 60]);

        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Midterm, 'score' => 30, 'max_score' => 40,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Final, 'score' => 54, 'max_score' => 60,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);

        // midterm 75% * 0.4 + final 90% * 0.6 = 84%
        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        $ranking = TermRanking::where('student_id', $this->student->id)->where('term_id', $this->term->id)->firstOrFail();
        $this->assertSame('84.00', $ranking->average);
    }

    public function test_ranking_uses_whichever_exam_type_is_approved_when_only_one_is(): void
    {
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Midterm, 'score' => 30, 'max_score' => 40,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Final, 'score' => 54, 'max_score' => 60,
            'status' => ResultStatus::Submitted, 'entered_by' => $this->teacher->id,
        ]);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        $ranking = TermRanking::where('student_id', $this->student->id)->where('term_id', $this->term->id)->firstOrFail();
        $this->assertSame('75.00', $ranking->average);
    }

    public function test_recomputing_rankings_does_not_erase_an_existing_remark(): void
    {
        TermRanking::create(['student_id' => $this->student->id, 'class_id' => $this->class->id, 'term_id' => $this->term->id, 'remark' => 'Great effort this term.']);

        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Final, 'score' => 54, 'max_score' => 60,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);

        $ranking = TermRanking::where('student_id', $this->student->id)->where('term_id', $this->term->id)->firstOrFail();
        $this->assertSame('Great effort this term.', $ranking->remark);
        $this->assertNotNull($ranking->position);
    }

    public function test_homeroom_teacher_can_write_a_remark(): void
    {
        $this->class->update(['homeroom_teacher_id' => $this->teacher->id]);

        Livewire::actingAs($this->teacher->user)
            ->test(TeacherRemarks::class, ['class' => $this->class])
            ->set('termId', (string) $this->term->id)
            ->set("remarks.{$this->student->id}", 'Excellent progress in mathematics.')
            ->call('save');

        $ranking = TermRanking::where('student_id', $this->student->id)->where('term_id', $this->term->id)->firstOrFail();
        $this->assertSame('Excellent progress in mathematics.', $ranking->remark);
    }

    public function test_a_teacher_who_is_not_the_homeroom_teacher_cannot_write_remarks(): void
    {
        $otherTeacherUser = User::factory()->create(['status' => UserStatus::Active]);
        $otherTeacherUser->roles()->attach(Role::where('name', 'Teacher')->first());
        Teacher::create(['user_id' => $otherTeacherUser->id, 'employee_no' => 'T2', 'status' => 'active', 'hire_date' => '2020-01-01']);

        Livewire::actingAs($otherTeacherUser)
            ->test(TeacherRemarks::class, ['class' => $this->class])
            ->assertForbidden();
    }

    public function test_admin_cannot_write_remarks_even_though_admin_bypasses_most_policies(): void
    {
        $ranking = TermRanking::create(['student_id' => $this->student->id, 'class_id' => $this->class->id, 'term_id' => $this->term->id]);

        $this->assertFalse($this->admin->can('update', $ranking));
    }

    public function test_admin_can_set_grade_weights_and_they_must_sum_to_100(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsEdit::class)
            ->set('midterm_weight', 50)
            ->set('final_weight', 40)
            ->call('save')
            ->assertHasErrors('final_weight');

        $this->assertSame(40, SchoolSetting::current()->fresh()->midterm_weight);

        Livewire::actingAs($this->admin)
            ->test(SettingsEdit::class)
            ->set('midterm_weight', 30)
            ->set('final_weight', 70)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(30, SchoolSetting::current()->fresh()->midterm_weight);
        $this->assertSame(70, SchoolSetting::current()->fresh()->final_weight);
    }

    public function test_student_sees_midterm_and_final_columns_plus_position_and_remark(): void
    {
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Midterm, 'score' => 30, 'max_score' => 40,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Final, 'score' => 54, 'max_score' => 60,
            'status' => ResultStatus::Approved, 'entered_by' => $this->teacher->id,
        ]);

        app(RankingService::class)->computeForClassTerm($this->class, $this->term);
        TermRanking::where('student_id', $this->student->id)->update(['remark' => 'Keep up the good work.']);

        Livewire::actingAs($this->student->user)
            ->test(StudentResults::class)
            ->set('termId', (string) $this->term->id)
            ->assertSee('Mathematics')
            ->assertSee('30')
            ->assertSee('54')
            ->assertSee('Keep up the good work.')
            ->assertSee('of 1');
    }

    public function test_student_only_sees_approved_entries_not_drafts_or_submitted(): void
    {
        ResultEntry::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'class_id' => $this->class->id,
            'term_id' => $this->term->id, 'exam_type' => ExamType::Midterm, 'score' => 30, 'max_score' => 40,
            'status' => ResultStatus::Submitted, 'entered_by' => $this->teacher->id,
        ]);

        Livewire::actingAs($this->student->user)
            ->test(StudentResults::class)
            ->set('termId', (string) $this->term->id)
            ->assertDontSee('Mathematics');
    }
}
