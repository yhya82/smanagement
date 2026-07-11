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

    public function create(): void
    {
        $validated = $this->validate();

        $temporaryPassword = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'status' => UserStatus::Active,
            'must_change_password' => true,
        ]);

        $user->roles()->attach($validated['role_id']);

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
