<?php

namespace App\Livewire\Teacher;

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
        $teacher = Auth::user()->teacher;
        $term = Term::active();

        $entries = collect();

        if ($teacher && $term) {
            $assignments = TeacherSubjectAssignment::where('teacher_id', $teacher->id)
                ->where('term_id', $term->id)
                ->where('is_active', true)
                ->get(['class_id', 'subject_id']);

            if ($assignments->isNotEmpty()) {
                $entries = TimetableEntry::where('term_id', $term->id)
                    ->where(function ($query) use ($assignments) {
                        foreach ($assignments as $assignment) {
                            $query->orWhere(function ($q) use ($assignment) {
                                $q->where('class_id', $assignment->class_id)->where('subject_id', $assignment->subject_id);
                            });
                        }
                    })
                    ->with(['subject', 'period', 'schoolClass'])
                    ->get()
                    ->keyBy(fn (TimetableEntry $entry) => "{$entry->day_of_week}:{$entry->period_id}");
            }
        }

        return view('livewire.teacher.timetable', [
            'periods' => Period::orderBy('sort_order')->get(),
            'entries' => $entries,
            'days' => TimetableEntry::DAYS,
            'term' => $term,
        ]);
    }
}
