<?php

namespace App\Livewire\Student;

use App\Enums\ExamType;
use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermRanking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * SRS §17: "Students only see approved results" - filtered at the query
 * level, not just hidden in the UI.
 */
#[Layout('components.app-layout')]
class Results extends Component
{
    #[Url]
    public string $termId = '';

    public function mount(): void
    {
        $this->termId = (string) (Term::where('is_active', true)->first()?->id ?? '');
    }

    public function render()
    {
        $student = Auth::user()->student;

        $subjects = collect();
        $ranking = null;
        $classSize = null;

        if ($this->termId) {
            $subjects = ResultEntry::where('student_id', $student->id)
                ->where('term_id', $this->termId)
                ->where('status', ResultStatus::Approved)
                ->with('subject')
                ->get()
                ->groupBy('subject_id')
                ->map(fn ($entries) => [
                    'subject' => $entries->first()->subject,
                    'midterm' => $entries->firstWhere('exam_type', ExamType::Midterm),
                    'final' => $entries->firstWhere('exam_type', ExamType::Final),
                ])
                ->values();

            $ranking = TermRanking::where('student_id', $student->id)
                ->where('term_id', $this->termId)
                ->first();

            if ($ranking) {
                $classSize = Student::where('current_class_id', $ranking->class_id)->count();
            }
        }

        return view('livewire.student.results', [
            'terms' => Term::orderBy('start_date')->get(),
            'subjects' => $subjects,
            'ranking' => $ranking,
            'classSize' => $classSize,
        ]);
    }
}
