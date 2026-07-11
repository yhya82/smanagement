<?php

namespace App\Livewire\Admin\Periods;

use App\Models\Period;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $name = '';

    public string $start_time = '';

    public string $end_time = '';

    public ?int $sort_order = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Period::class);

        $this->sort_order = (Period::max('sort_order') ?? 0) + 1;
    }

    public function create(): void
    {
        $this->authorize('create', Period::class);

        $validated = $this->validate();

        Period::create($validated);

        $this->reset(['name', 'start_time', 'end_time', 'showCreateForm']);
        $this->sort_order = (Period::max('sort_order') ?? 0) + 1;
    }

    public function render()
    {
        return view('livewire.admin.periods.index', [
            'periods' => Period::orderBy('sort_order')->get(),
        ]);
    }
}
