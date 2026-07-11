<?php

namespace App\Livewire\Admin\Settings;

use App\Models\CalendarEvent;
use App\Models\Term;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Calendar extends Component
{
    public string $termId = '';

    public string $date = '';

    public string $title = '';

    public string $type = 'holiday';

    protected function rules(): array
    {
        return [
            'termId' => ['required', 'exists:terms,id'],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:holiday,event'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $this->termId = (string) (Term::active()?->id ?? '');
    }

    public function create(): void
    {
        $this->authorize('create', CalendarEvent::class);

        $validated = $this->validate();

        CalendarEvent::create([
            'term_id' => $validated['termId'],
            'date' => $validated['date'],
            'title' => $validated['title'],
            'type' => $validated['type'],
        ]);

        $this->reset(['date', 'title']);
        $this->type = 'holiday';
    }

    public function delete(CalendarEvent $calendarEvent): void
    {
        $this->authorize('delete', $calendarEvent);

        $calendarEvent->delete();
    }

    public function render()
    {
        $term = $this->termId ? Term::find($this->termId) : null;

        $events = $term
            ? CalendarEvent::where('term_id', $term->id)->orderBy('date')->get()
            : collect();

        $weeks = $term ? (int) ceil($term->start_date->diffInDays($term->end_date) / 7) : null;

        return view('livewire.admin.settings.calendar', [
            'terms' => Term::orderBy('start_date')->get(),
            'term' => $term,
            'events' => $events,
            'weeks' => $weeks,
        ]);
    }
}
