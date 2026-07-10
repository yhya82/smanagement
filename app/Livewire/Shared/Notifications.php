<?php

namespace App\Livewire\Shared;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Shared across every role, not just Student - notifications are purely
 * ownership-scoped (SRS §19: every actor gets their own), and Admin/
 * Registrar/Teacher all have notifications firing to them (ApplicationSubmitted,
 * ApplicationDecided, SubjectAssigned) with nowhere to read them before this.
 */
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

        return view('livewire.shared.notifications', ['notifications' => $notifications]);
    }
}
