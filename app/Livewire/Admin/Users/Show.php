<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\Role;
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

    public string $role_id = '';

    public ?string $temporaryPassword = null;

    public ?string $statusError = null;

    public ?string $roleError = null;

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
        $this->role_id = (string) ($user->roles->first()?->id ?? '');
    }

    public function updateDetails(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate();

        $this->user->update($validated);
    }

    /**
     * Restricted to Registrar/Administrator/custom roles (see
     * Role::scopeAssignableViaUserManagement) - both directions: a user
     * who currently holds Teacher or Student can't be reassigned away
     * from it here (their profile row lives elsewhere, under the
     * dedicated onboarding flow), and the scoped query on the submitted
     * role_id itself blocks assigning INTO Teacher/Student even if a
     * request were crafted to bypass the dropdown's own options.
     */
    public function updateRole(): void
    {
        $this->authorize('update', $this->user);

        $this->roleError = null;

        if ($this->userHoldsOnboardingManagedRole()) {
            $this->roleError = 'This user holds a Teacher or Student role, which can only be changed through their own onboarding flow.';

            return;
        }

        $this->validate(['role_id' => ['required', 'exists:roles,id']]);

        $role = Role::assignableViaUserManagement()->find($this->role_id);

        if (! $role) {
            $this->roleError = 'That role cannot be assigned here.';

            return;
        }

        $this->user->roles()->sync([$role->id]);
    }

    public function userHoldsOnboardingManagedRole(): bool
    {
        return $this->user->roles()->whereIn('name', ['Teacher', 'Student'])->exists();
    }

    public function toggleStatus(): void
    {
        $this->authorize('update', $this->user);

        $this->statusError = null;

        if ($this->user->id === Auth::id()) {
            $this->statusError = 'You cannot deactivate your own account.';

            return;
        }

        $newStatus = $this->user->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active;

        $this->user->update(['status' => $newStatus]);

        // Same reasoning as resetPassword(): deactivating an account should
        // actually stop it, not just block its next login attempt.
        if ($newStatus === UserStatus::Inactive) {
            $this->user->invalidateOtherSessions();
        }
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

        // A reset is meaningless if a session from before it keeps working -
        // this is what actually locks out a suspected-compromised account,
        // not the new password alone.
        $this->user->invalidateOtherSessions();

        $this->temporaryPassword = $temporaryPassword;
    }

    public function render()
    {
        return view('livewire.admin.users.show', [
            'roleNames' => $this->user->roles->pluck('name')->join(', ') ?: 'None',
            'roles' => Role::assignableViaUserManagement()->get(),
            'canReassignRole' => ! $this->userHoldsOnboardingManagedRole(),
        ]);
    }
}
