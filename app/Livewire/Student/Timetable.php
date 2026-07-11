<?php

namespace App\Livewire\Student;

use App\Models\Period;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\TimetableEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Timetable extends Component
{
    public function render()
    {
        $student = Auth::user()->student;
        $term = Term::active();

        $entries = collect();
        $teacherMap = collect();

        if ($student->current_class_id && $term) {
            $entries = TimetableEntry::where('class_id', $student->current_class_id)
                ->where('term_id', $term->id)
                ->with(['subject', 'period'])
                ->get()
                ->keyBy(fn (TimetableEntry $entry) => "{$entry->day_of_week}:{$entry->period_id}");

            $teacherMap = TeacherSubjectAssignment::activeTeacherMap($term->id, $student->current_class_id);
        }

        return view('livewire.student.timetable', [
            'periods' => Period::orderBy('sort_order')->get(),
            'entries' => $entries,
            'teacherMap' => $teacherMap,
            'days' => TimetableEntry::DAYS,
            'term' => $term,
        ]);
    }
}
