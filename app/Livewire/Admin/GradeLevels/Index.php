<?php

namespace App\Livewire\Admin\GradeLevels;

use App\Models\GradeLevel;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $name = '';

    public ?int $sort_order = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:grade_levels,name'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Defaults to "one after the last grade level" so the admin isn't
     * guessing at a number - reduces manual entry for the common case of
     * appending a new level at the end of the sequence.
     */
    public function mount(): void
    {
        $this->sort_order = (GradeLevel::max('sort_order') ?? 0) + 1;
    }

    public function create(): void
    {
        $this->authorize('create', GradeLevel::class);

        $validated = $this->validate();

        GradeLevel::create($validated);

        $this->reset(['name', 'showCreateForm']);
        $this->sort_order = (GradeLevel::max('sort_order') ?? 0) + 1;
    }

    public function render()
    {
        return view('livewire.admin.grade-levels.index', [
            'gradeLevels' => GradeLevel::withCount('classes')->orderBy('sort_order')->get(),
        ]);
    }
}
