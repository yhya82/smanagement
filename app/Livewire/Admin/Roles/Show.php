<?php

namespace App\Livewire\Admin\Roles;

use App\Models\Permission;
use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Show extends Component
{
    public Role $role;

    public string $name = '';

    public string $description = '';

    /** @var list<int> */
    public array $selectedPermissionIds = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->role->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(Role $role): void
    {
        $this->authorize('view', $role);

        $this->role = $role;
        $this->name = $role->name;
        $this->description = $role->description ?? '';
        $this->selectedPermissionIds = $role->permissions()->pluck('permissions.id')->all();
    }

    /**
     * System roles (the 4 SRS actors) keep their fixed name - only their
     * description and permission set can be tuned, since routes/middleware
     * elsewhere match on the exact role name (role:Administrator etc.).
     */
    public function updateDetails(): void
    {
        $this->authorize('update', $this->role);

        if ($this->role->is_system) {
            $this->validate(['description' => ['nullable', 'string', 'max:1000']]);
            $this->role->update(['description' => $this->description]);

            return;
        }

        $validated = $this->validate();
        $this->role->update($validated);
    }

    public function savePermissions(): void
    {
        $this->authorize('update', $this->role);

        // selectedPermissionIds is a public Livewire property, so a tampered
        // request could submit IDs beyond the checkboxes actually rendered -
        // sync() would happily attach a permission the UI never offered.
        $validIds = Permission::whereIn('id', $this->selectedPermissionIds)->pluck('id')->all();

        $this->role->permissions()->sync($validIds);
    }

    public function render()
    {
        return view('livewire.admin.roles.show', [
            'permissions' => Permission::orderBy('key')->get(),
        ]);
    }
}
