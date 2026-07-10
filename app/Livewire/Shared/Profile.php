<?php

namespace App\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Profile extends Component
{
    public function render()
    {
        return view('livewire.shared.profile', [
            'user' => Auth::user()->load(['roles', 'teacher', 'student']),
        ]);
    }
}
