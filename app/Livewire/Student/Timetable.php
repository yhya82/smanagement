<?php

namespace App\Livewire\Student;

use App\Models\Period;
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
        $term = Term::where('is_active', true)->first();

        $entries = collect();

        if ($student->current_class_id && $term) {
            $entries = TimetableEntry::where('class_id', $student->current_class_id)
                ->where('term_id', $term->id)
                ->with(['subject', 'period'])
                ->get()
                ->keyBy(fn (TimetableEntry $entry) => "{$entry->day_of_week}:{$entry->period_id}");
        }

        return view('livewire.student.timetable', [
            'periods' => Period::orderBy('sort_order')->get(),
            'entries' => $entries,
            'days' => TimetableEntry::DAYS,
            'term' => $term,
        ]);
    }
}
