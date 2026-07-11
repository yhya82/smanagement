<div class="max-w-md">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Change Password</h1>

    @if ($forced)
        <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/30 p-3 text-sm text-yellow-800 dark:text-yellow-400">
            You must set a new password before continuing.
        </div>
    @endif

    <form wire:submit="updatePassword" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Current password</label>
            <input type="password" wire:model="current_password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            @error('current_password') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">New password</label>
            <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            @error('password') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Confirm new password</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
        </div>
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Update password
        </button>
    </form>
</div>
