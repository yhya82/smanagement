<?php

namespace App\Livewire\Teacher;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermRanking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Remarks extends Component
{
    public SchoolClass $class;

    public string $termId = '';

    /** @var array<int, string> keyed by student_id */
    public array $remarks = [];

    public ?string $statusMessage = null;

    public function mount(SchoolClass $class): void
    {
        $teacher = Auth::user()->teacher;

        abort_unless($teacher && $teacher->id === $class->homeroom_teacher_id, 403);

        $this->class = $class;
        $this->termId = (string) (Term::where('is_active', true)->first()?->id ?? '');
        $this->loadRemarks();
    }

    public function updatedTermId(): void
    {
        $this->loadRemarks();
    }

    private function loadRemarks(): void
    {
        if (! $this->termId) {
            $this->remarks = [];

            return;
        }

        $existing = TermRanking::where('class_id', $this->class->id)
            ->where('term_id', $this->termId)
            ->pluck('remark', 'student_id');

        $this->remarks = Student::where('current_class_id', $this->class->id)
            ->get()
            ->mapWithKeys(fn (Student $student) => [$student->id => $existing[$student->id] ?? ''])
            ->all();
    }

    public function save(): void
    {
        // Re-check every key against the class roster at save time - see
        // the identical guard in Teacher/Attendance.php::save().
        $classStudentIds = Student::where('current_class_id', $this->class->id)->pluck('id')->all();

        foreach ($this->remarks as $studentId => $remark) {
            if (! in_array($studentId, $classStudentIds, true)) {
                continue;
            }

            $ranking = TermRanking::firstOrNew(['student_id' => $studentId, 'term_id' => $this->termId]);
            $ranking->class_id = $this->class->id;
            $ranking->remark = $remark !== '' ? $remark : null;
            $ranking->save();
        }

        $this->statusMessage = 'Remarks saved.';
    }

    public function render()
    {
        return view('livewire.teacher.remarks', [
            'students' => Student::where('current_class_id', $this->class->id)->orderBy('last_name')->get(),
            'terms' => Term::orderBy('start_date')->get(),
        ]);
    }
}
