<?php

namespace App\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $forced;

    public function mount(): void
    {
        // The banner ("you must change your password before continuing")
        // only makes sense the first time - once must_change_password is
        // cleared, this same page still works as an ordinary "change my
        // password" screen reachable from the profile menu.
        $this->forced = Auth::user()->must_change_password;
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user->forceFill(['password' => Hash::make($this->password), 'must_change_password' => false])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('status', 'Password updated.');

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.shared.change-password');
    }
}
