<?php

namespace App\Livewire\Admin\Roles;

use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $name = '';

    public string $description = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function create(): void
    {
        $this->authorize('create', Role::class);

        $validated = $this->validate();

        Role::create([
            ...$validated,
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->reset(['name', 'description', 'showCreateForm']);
    }

    /**
     * Disabling has the same blast radius as deleting (every holder loses
     * the role immediately, see User::hasRole()) - restricted to non-system
     * roles for the same reason RolePolicy blocks deleting them.
     */
    public function toggleActive(Role $role): void
    {
        $this->authorize('update', $role);

        if ($role->is_system) {
            return;
        }

        $role->update(['is_active' => ! $role->is_active]);
    }

    public function render()
    {
        return view('livewire.admin.roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
        ]);
    }
}
