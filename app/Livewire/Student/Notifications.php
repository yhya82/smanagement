<?php

namespace App\Livewire\Student;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Notifications extends Component
{
    use WithPagination;

    public function markRead(Notification $notification): void
    {
        $this->authorize('update', $notification);

        $notification->update(['is_read' => true, 'read_at' => now()]);
    }

    public function render()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(15);

        return view('livewire.student.notifications', ['notifications' => $notifications]);
    }
}
