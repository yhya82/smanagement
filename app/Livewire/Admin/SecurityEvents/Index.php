<?php

namespace App\Livewire\Admin\SecurityEvents;

use App\Models\SecurityEvent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $event = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SecurityEvent::class);
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $events = SecurityEvent::query()
            ->with('user')
            ->when($this->event, fn ($query) => $query->where('event', $this->event))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.security-events.index', [
            'events' => $events,
            'eventTypes' => SecurityEvent::query()->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
