<?php

namespace App\Livewire\Teacher;

use App\Enums\ExamType;
use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Grades extends Component
{
    public SchoolClass $class;

    public Subject $subject;

    public ?Term $term = null;

    /** @var array<int, array{midterm: array{score: string, max_score: string}, final: array{score: string, max_score: string}}> keyed by student_id */
    public array $entries = [];

    public ?string $statusMessage = null;

    public function mount(SchoolClass $class, Subject $subject): void
    {
        $teacher = Auth::user()->teacher;

        abort_unless($teacher && $teacher->hasAccessToClassSubject($class->id, $subject->id), 403);

        $this->class = $class;
        $this->subject = $subject;
        $this->term = Term::active();

        $this->loadEntries();
    }

    private function loadEntries(): void
    {
        if (! $this->term) {
            return;
        }

        $existing = ResultEntry::where('class_id', $this->class->id)
            ->where('subject_id', $this->subject->id)
            ->where('term_id', $this->term->id)
            ->get()
            ->groupBy('student_id');

        $this->entries = Student::where('current_class_id', $this->class->id)
            ->get()
            ->mapWithKeys(function (Student $student) use ($existing) {
                $studentEntries = $existing->get($student->id, collect());
                $midterm = $studentEntries->firstWhere('exam_type', ExamType::Midterm);
                $final = $studentEntries->firstWhere('exam_type', ExamType::Final);

                return [$student->id => [
                    'midterm' => ['score' => $midterm ? (string) $midterm->score : '', 'max_score' => $midterm ? (string) $midterm->max_score : '40'],
                    'final' => ['score' => $final ? (string) $final->score : '', 'max_score' => $final ? (string) $final->max_score : '60'],
                ]];
            })
            ->all();
    }

    public function saveDrafts(ResultService $resultService): void
    {
        $teacher = Auth::user()->teacher;

        // Re-check every key against the class roster at save time - see
        // the identical guard in Teacher/Attendance.php::save().
        $classStudentIds = Student::where('current_class_id', $this->class->id)->pluck('id')->all();

        foreach (array_keys($this->entries) as $studentId) {
            if (! in_array($studentId, $classStudentIds, true)) {
                continue;
            }

            foreach (ExamType::cases() as $examType) {
                $values = $this->entries[$studentId][$examType->value];

                if ($values['score'] === '' || $values['max_score'] === '') {
                    continue;
                }

                $existing = ResultEntry::where('student_id', $studentId)
                    ->where('subject_id', $this->subject->id)
                    ->where('term_id', $this->term->id)
                    ->where('exam_type', $examType)
                    ->first();

                // Approved/submitted entries are left alone here - only drafts
                // (or brand-new rows) get written by this bulk save.
                if ($existing && $existing->status !== ResultStatus::Draft) {
                    continue;
                }

                $resultService->saveDraft(
                    Student::find($studentId),
                    $this->subject,
                    $this->class,
                    $this->term,
                    $examType,
                    (float) $values['score'],
                    (float) $values['max_score'],
                    $teacher
                );
            }
        }

        $this->statusMessage = 'Draft scores saved.';
        $this->loadEntries();
    }

    public function submitMidterm(ResultService $resultService): void
    {
        $this->submitExamType(ExamType::Midterm, $resultService);
    }

    public function submitFinal(ResultService $resultService): void
    {
        $this->submitExamType(ExamType::Final, $resultService);
    }

    private function submitExamType(ExamType $examType, ResultService $resultService): void
    {
        $drafts = ResultEntry::where('class_id', $this->class->id)
            ->where('subject_id', $this->subject->id)
            ->where('term_id', $this->term->id)
            ->where('exam_type', $examType)
            ->where('status', ResultStatus::Draft)
            ->get();

        foreach ($drafts as $draft) {
            $resultService->submit($draft);
        }

        $label = $examType === ExamType::Midterm ? 'midterm' : 'final';
        $this->statusMessage = "Submitted {$drafts->count()} {$label} result(s) for admin approval.";
        $this->loadEntries();
    }

    public function render()
    {
        $statuses = collect();

        if ($this->term) {
            $statuses = ResultEntry::where('class_id', $this->class->id)
                ->where('subject_id', $this->subject->id)
                ->where('term_id', $this->term->id)
                ->get()
                ->groupBy('student_id')
                ->map(fn ($entries) => $entries->mapWithKeys(fn (ResultEntry $entry) => [$entry->exam_type->value => $entry->status->value]));
        }

        return view('livewire.teacher.grades', [
            'students' => Student::where('current_class_id', $this->class->id)->orderBy('last_name')->get(),
            'statuses' => $statuses,
        ]);
    }
}
