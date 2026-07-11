<div class="max-w-lg">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
        <div class="flex items-center gap-3">
            <span @class([
                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $user->status->value === 'active',
                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $user->status->value !== 'active',
            ])>
                {{ ucfirst($user->status->value) }}
            </span>
            <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200">Back to users</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Details</h2>
        <form wire:submit="updateDetails" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('email') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                Save
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Role</h2>

        @if ($roleError)
            <div class="rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">{{ $roleError }}</div>
        @endif

        @if ($canReassignRole)
            <form wire:submit="updateRole" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Role</label>
                    <select wire:model="role_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">None</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                    Save
                </button>
            </form>
        @else
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $roleNames }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Teacher and Student roles are managed through their own onboarding flow, not here.
            </p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Account status</h2>

        @if ($statusError)
            <div class="rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">{{ $statusError }}</div>
        @endif

        <p class="text-sm text-gray-600 dark:text-gray-400">
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

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Password</h2>

        @if ($temporaryPassword)
            <div class="rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-4 text-sm text-green-800 dark:text-green-400">
                <p class="font-medium">Password reset.</p>
                <p class="mt-1">Temporary password: <code class="bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded border border-green-200 font-mono">{{ $temporaryPassword }}</code></p>
                <p class="mt-1 text-green-700 dark:text-green-400">Share this with them directly - it will not be shown again. They'll be asked to set their own password on next login.</p>
            </div>
        @endif

        <p class="text-sm text-gray-600 dark:text-gray-400">Generates a new temporary password and requires the user to set their own on next login.</p>
        <button type="button" wire:click="resetPassword" wire:confirm="Reset this user's password?"
            class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
            Reset password
        </button>
    </div>
</div>
