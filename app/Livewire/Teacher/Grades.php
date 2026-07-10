<?php

namespace App\Livewire\Teacher;

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

    /** @var array<int, array{score: string, max_score: string}> keyed by student_id */
    public array $entries = [];

    public ?string $statusMessage = null;

    public function mount(SchoolClass $class, Subject $subject): void
    {
        $teacher = Auth::user()->teacher;

        abort_unless($teacher && $teacher->hasAccessToClassSubject($class->id, $subject->id), 403);

        $this->class = $class;
        $this->subject = $subject;
        $this->term = Term::where('is_active', true)->first();

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
            ->keyBy('student_id');

        $this->entries = Student::where('current_class_id', $this->class->id)
            ->get()
            ->mapWithKeys(function (Student $student) use ($existing) {
                $entry = $existing->get($student->id);

                return [$student->id => [
                    'score' => $entry ? (string) $entry->score : '',
                    'max_score' => $entry ? (string) $entry->max_score : '100',
                ]];
            })
            ->all();
    }

    public function saveDrafts(ResultService $resultService): void
    {
        $teacher = Auth::user()->teacher;

        foreach ($this->entries as $studentId => $values) {
            if ($values['score'] === '' || $values['max_score'] === '') {
                continue;
            }

            $student = Student::find($studentId);
            $existing = ResultEntry::where('student_id', $studentId)
                ->where('subject_id', $this->subject->id)
                ->where('term_id', $this->term->id)
                ->first();

            // Approved/submitted entries are left alone here - only drafts
            // (or brand-new rows) get written by this bulk save.
            if ($existing && $existing->status !== ResultStatus::Draft) {
                continue;
            }

            $resultService->saveDraft(
                $student,
                $this->subject,
                $this->class,
                $this->term,
                (float) $values['score'],
                (float) $values['max_score'],
                $teacher
            );
        }

        $this->statusMessage = 'Draft scores saved.';
        $this->loadEntries();
    }

    public function submitAll(ResultService $resultService): void
    {
        $drafts = ResultEntry::where('class_id', $this->class->id)
            ->where('subject_id', $this->subject->id)
            ->where('term_id', $this->term->id)
            ->where('status', ResultStatus::Draft)
            ->get();

        foreach ($drafts as $draft) {
            $resultService->submit($draft);
        }

        $this->statusMessage = "Submitted {$drafts->count()} result(s) for admin approval.";
        $this->loadEntries();
    }

    public function render()
    {
        $statuses = $this->term
            ? ResultEntry::where('class_id', $this->class->id)
                ->where('subject_id', $this->subject->id)
                ->where('term_id', $this->term->id)
                ->pluck('status', 'student_id')
            : collect();

        return view('livewire.teacher.grades', [
            'students' => Student::where('current_class_id', $this->class->id)->orderBy('last_name')->get(),
            'statuses' => $statuses,
        ]);
    }
}
