<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Show extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public ?string $temporaryPassword = null;

    public ?string $statusError = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
        ];
    }

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateDetails(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate();

        $this->user->update($validated);
    }

    public function toggleStatus(): void
    {
        $this->authorize('update', $this->user);

        $this->statusError = null;

        if ($this->user->id === Auth::id()) {
            $this->statusError = 'You cannot deactivate your own account.';

            return;
        }

        $this->user->update([
            'status' => $this->user->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ]);
    }

    /**
     * The admin never learns the account's actual current password (it's
     * either a random un-communicated string from onboarding, or something
     * only the user themself knows) - resetting is the only way to hand
     * them a fresh one to relay out-of-band.
     */
    public function resetPassword(): void
    {
        $this->authorize('update', $this->user);

        $temporaryPassword = Str::password(12);

        $this->user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        $this->temporaryPassword = $temporaryPassword;
    }

    public function render()
    {
        return view('livewire.admin.users.show', [
            'roleNames' => $this->user->roles->pluck('name')->join(', ') ?: 'None',
        ]);
    }
}
