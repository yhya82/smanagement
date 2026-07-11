<?php

namespace App\Livewire\Admin\Timetable;

use App\Models\ClassSubject;
use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TimetableEntry;
use App\Services\TimetableService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

#[Layout('components.app-layout')]
class Show extends Component
{
    public SchoolClass $class;

    #[Url]
    public string $termId = '';

    public ?string $editingSlot = null;

    public string $editingSubjectId = '';

    public ?string $generateResult = null;

    public ?string $slotError = null;

    public function mount(SchoolClass $class): void
    {
        $this->authorize('viewAny', TimetableEntry::class);

        $this->class = $class;
        $this->termId = (string) (Term::active()?->id ?? '');
    }

    public function generate(TimetableService $timetableService): void
    {
        $this->authorize('create', TimetableEntry::class);

        $this->generateResult = null;

        if (! $this->termId) {
            $this->generateResult = 'Select a term first.';

            return;
        }

        try {
            $result = $timetableService->generate($this->class, Term::findOrFail($this->termId));
            $this->generateResult = "Created {$result['created']} slot(s); {$result['skipped']} slot(s) skipped.";
        } catch (RuntimeException $e) {
            $this->generateResult = $e->getMessage();
        }
    }

    public function openSlot(string $day, int $periodId): void
    {
        $this->authorize('create', TimetableEntry::class);

        $this->slotError = null;
        $this->editingSlot = "{$day}:{$periodId}";

        $existing = TimetableEntry::where([
            'class_id' => $this->class->id, 'term_id' => $this->termId,
            'period_id' => $periodId, 'day_of_week' => $day,
        ])->first();

        $this->editingSubjectId = $existing ? (string) $existing->subject_id : '';
    }

    public function cancelSlot(): void
    {
        $this->editingSlot = null;
    }

    public function saveSlot(TimetableService $timetableService): void
    {
        $this->authorize('create', TimetableEntry::class);

        $this->slotError = null;

        [$day, $periodId] = explode(':', $this->editingSlot);
        $period = Period::findOrFail($periodId);
        $term = Term::findOrFail($this->termId);
        $subject = $this->editingSubjectId !== '' ? Subject::findOrFail($this->editingSubjectId) : null;

        try {
            $timetableService->setEntry($this->class, $term, $period, $day, $subject, Auth::user());
        } catch (RuntimeException $e) {
            $this->slotError = $e->getMessage();

            return;
        }

        $this->editingSlot = null;
    }

    public function render()
    {
        $entries = collect();
        $teacherMap = collect();

        if ($this->termId) {
            $entries = TimetableEntry::where('class_id', $this->class->id)
                ->where('term_id', $this->termId)
                ->with(['subject', 'period'])
                ->get()
                ->keyBy(fn (TimetableEntry $entry) => "{$entry->day_of_week}:{$entry->period_id}");

            // One query for the whole grid's teacher names instead of one
            // per visible cell (TimetableEntry::teacher() re-queries every
            // call) - re-rendered on every slot open/save/cancel, so this
            // matters far more here than it would on a one-shot page load.
            $teacherMap = TeacherSubjectAssignment::activeTeacherMap($this->termId, $this->class->id);
        }

        $classSubjects = $this->termId
            ? ClassSubject::where('class_id', $this->class->id)->where('term_id', $this->termId)->with('subject')->get()->pluck('subject')
            : collect();

        return view('livewire.admin.timetable.show', [
            'periods' => Period::orderBy('sort_order')->get(),
            'terms' => Term::orderBy('name')->get(),
            'entries' => $entries,
            'teacherMap' => $teacherMap,
            'classSubjects' => $classSubjects,
            'days' => TimetableEntry::DAYS,
        ]);
    }
}
