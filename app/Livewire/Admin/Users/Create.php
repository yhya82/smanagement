<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Student and Teacher accounts already have dedicated onboarding flows
 * (application approval, teacher onboarding, bulk import) that also create
 * the linked Student/Teacher profile row a bare User wouldn't have - this
 * screen is for every OTHER role: Registrar and Administrator staff
 * accounts, plus any admin-created custom role (which has no linked
 * profile table of its own, same as Registrar/Administrator).
 */
#[Layout('components.app-layout')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role_id = '';

    public ?string $temporaryPassword = null;

    public ?string $roleError = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    /**
     * Same scoped lookup as Show::updateRole() - role_id is a public
     * Livewire property, so a tampered request could submit the Teacher or
     * Student role's ID even though the rendered dropdown never offers it.
     * Those two roles' accounts are provisioned through their own
     * onboarding flows (which also create the linked Teacher/Student
     * profile row) - attaching one here would create a user holding that
     * role with no such profile, breaking every policy/observer that
     * assumes one exists.
     */
    public function create(): void
    {
        $validated = $this->validate();

        $this->roleError = null;

        $role = Role::assignableViaUserManagement()->find($validated['role_id']);

        if (! $role) {
            $this->roleError = 'That role cannot be assigned here.';

            return;
        }

        $temporaryPassword = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'status' => UserStatus::Active,
            'must_change_password' => true,
        ]);

        $user->roles()->attach($role->id);

        $this->temporaryPassword = $temporaryPassword;
        $this->reset(['name', 'email', 'role_id']);
    }

    public function render()
    {
        return view('livewire.admin.users.create', [
            'roles' => Role::assignableViaUserManagement()->get(),
        ]);
    }
}
