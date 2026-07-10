<?php

namespace App\Livewire\Admin\Teachers;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Show extends Component
{
    public Teacher $teacher;

    public string $subject_id = '';

    public string $class_id = '';

    public string $term_id = '';

    public function mount(Teacher $teacher): void
    {
        $this->authorize('view', $teacher);

        $this->teacher = $teacher;
        $this->term_id = (string) (Term::where('is_active', true)->first()?->id ?? '');
    }

    protected function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ];
    }

    public function assign(): void
    {
        $this->authorize('create', TeacherSubjectAssignment::class);

        $validated = $this->validate();

        $exists = TeacherSubjectAssignment::where('teacher_id', $this->teacher->id)
            ->where('subject_id', $validated['subject_id'])
            ->where('class_id', $validated['class_id'])
            ->where('term_id', $validated['term_id'])
            ->exists();

        if ($exists) {
            $this->addError('subject_id', 'This teacher already has this exact assignment.');

            return;
        }

        // Fires TeacherActivationObserver (flips pending -> active on first
        // assignment) and a SubjectAssigned notification to the teacher.
        TeacherSubjectAssignment::create([
            'teacher_id' => $this->teacher->id,
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'],
            'term_id' => $validated['term_id'],
        ]);

        $activeTermId = $this->term_id;
        $this->reset(['subject_id', 'class_id']);
        $this->term_id = $activeTermId;
        $this->teacher->refresh();
    }

    public function remove(TeacherSubjectAssignment $assignment): void
    {
        $this->authorize('delete', $assignment);

        abort_unless($assignment->teacher_id === $this->teacher->id, 404);

        $assignment->delete();
    }

    public function render()
    {
        return view('livewire.admin.teachers.show', [
            'assignments' => TeacherSubjectAssignment::where('teacher_id', $this->teacher->id)
                ->with(['subject', 'schoolClass', 'term'])
                ->latest()
                ->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'terms' => Term::orderBy('start_date')->get(),
        ]);
    }
}
