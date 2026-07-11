<?php

namespace App\Livewire\Admin\Classes;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TimetableEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Subjects extends Component
{
    public SchoolClass $class;

    public string $subject_id = '';

    public string $term_id = '';

    public function mount(SchoolClass $class): void
    {
        $this->authorize('view', $class);

        $this->class = $class;
        $this->term_id = (string) (Term::active()?->id ?? '');
    }

    protected function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ];
    }

    public function assign(): void
    {
        $this->authorize('create', ClassSubject::class);

        $validated = $this->validate();

        $exists = ClassSubject::where('class_id', $this->class->id)
            ->where('subject_id', $validated['subject_id'])
            ->where('term_id', $validated['term_id'])
            ->exists();

        if ($exists) {
            $this->addError('subject_id', 'This subject is already assigned for that term.');

            return;
        }

        ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $validated['subject_id'],
            'term_id' => $validated['term_id'],
        ]);

        $activeTermId = $this->term_id;
        $this->reset('subject_id');
        $this->term_id = $activeTermId;
    }

    public function remove(ClassSubject $classSubject): void
    {
        $this->authorize('delete', $classSubject);

        abort_unless($classSubject->class_id === $this->class->id, 404);

        // A subject no longer assigned to this class has no business still
        // occupying timetable slots for it - otherwise it'd keep showing
        // (and the admin grid's subject picker only offers still-assigned
        // subjects, so a stale slot couldn't even be edited back).
        TimetableEntry::where('class_id', $classSubject->class_id)
            ->where('term_id', $classSubject->term_id)
            ->where('subject_id', $classSubject->subject_id)
            ->delete();

        $classSubject->delete();
    }

    public function render()
    {
        return view('livewire.admin.classes.subjects', [
            'assignments' => ClassSubject::where('class_id', $this->class->id)
                ->with(['subject', 'term'])
                ->latest()
                ->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'terms' => Term::orderBy('start_date')->get(),
        ]);
    }
}
