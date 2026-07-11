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

    public ?string $permissionsError = null;

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

    /**
     * `RolePolicy::update()` only checks the `roles.manage` permission, with
     * no special case for which role is being edited - so without this
     * guard, an Administrator (or anyone else holding `roles.manage`) could
     * strip `roles.manage` itself from the Administrator role and lock
     * every admin out of role management app-wide, or use it to grant a
     * narrower custom role permissions well beyond what it was scoped for.
     * Administrator is meant to always hold every permission (that's the
     * whole premise behind its Gate::before bypass) - it isn't meant to be
     * tuned through this screen at all.
     */
    public function isAdministratorRole(): bool
    {
        return $this->role->name === 'Administrator';
    }

    public function savePermissions(): void
    {
        $this->authorize('update', $this->role);

        $this->permissionsError = null;

        if ($this->isAdministratorRole()) {
            $this->permissionsError = "The Administrator role always has every permission and can't be edited here.";

            return;
        }

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
