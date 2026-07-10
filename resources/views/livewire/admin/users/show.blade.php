<div class="max-w-lg">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ $user->name }}</h1>
        <div class="flex items-center gap-3">
            <span @class([
                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                'bg-green-100 text-green-800' => $user->status->value === 'active',
                'bg-gray-100 text-gray-600' => $user->status->value !== 'active',
            ])>
                {{ ucfirst($user->status->value) }}
            </span>
            <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Back to users</a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900">Details</h2>
        <form wire:submit="updateDetails" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-700">Name</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700">Email</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700">Role</label>
                <p class="mt-1 text-sm text-gray-900">{{ $roleNames }}</p>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                Save
            </button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900">Account status</h2>

        @if ($statusError)
            <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $statusError }}</div>
        @endif

        <p class="text-sm text-gray-600">
            @if ($user->status->value === 'active')
                This account can currently log in.
            @else
                This account is deactivated and cannot log in.
            @endif
        </p>
        <button type="button" wire:click="toggleStatus"
            wire:confirm="{{ $user->status->value === 'active' ? 'Deactivate this account?' : 'Reactivate this account?' }}"
            @class([
                'text-white text-sm font-medium px-4 py-2 rounded-md',
                'bg-red-600 hover:bg-red-500' => $user->status->value === 'active',
                'bg-green-600 hover:bg-green-500' => $user->status->value !== 'active',
            ])>
            {{ $user->status->value === 'active' ? 'Deactivate' : 'Reactivate' }}
        </button>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Password</h2>

        @if ($temporaryPassword)
            <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800">
                <p class="font-medium">Password reset.</p>
                <p class="mt-1">Temporary password: <code class="bg-white px-1.5 py-0.5 rounded border border-green-200 font-mono">{{ $temporaryPassword }}</code></p>
                <p class="mt-1 text-green-700">Share this with them directly - it will not be shown again. They'll be asked to set their own password on next login.</p>
            </div>
        @endif

        <p class="text-sm text-gray-600">Generates a new temporary password and requires the user to set their own on next login.</p>
        <button type="button" wire:click="resetPassword" wire:confirm="Reset this user's password?"
            class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
            Reset password
        </button>
    </div>
</div>
