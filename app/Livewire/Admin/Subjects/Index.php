<?php

namespace App\Livewire\Admin\Subjects;

use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $name = '';

    public string $code = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Subject::class);

        $validated = $this->validate();

        Subject::create($validated);

        $this->reset(['name', 'code', 'showCreateForm']);
    }

    public function render()
    {
        return view('livewire.admin.subjects.index', [
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }
}
