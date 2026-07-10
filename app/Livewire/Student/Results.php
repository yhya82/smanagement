<?php

namespace App\Livewire\Student;

use App\Enums\ResultStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * SRS §17: "Students only see approved results" - filtered at the query
 * level, not just hidden in the UI.
 */
#[Layout('components.app-layout')]
class Results extends Component
{
    public function render()
    {
        $student = Auth::user()->student;

        $results = $student->resultEntries()
            ->where('status', ResultStatus::Approved)
            ->with(['subject', 'term'])
            ->latest('approved_at')
            ->get();

        return view('livewire.student.results', ['results' => $results]);
    }
}
