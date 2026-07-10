<div class="max-w-md">
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Change Password</h1>

    @if ($forced)
        <div class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-800">
            You must set a new password before continuing.
        </div>
    @endif

    <form wire:submit="updatePassword" class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <div>
            <label class="block text-sm text-gray-700">Current password</label>
            <input type="password" wire:model="current_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-gray-700">New password</label>
            <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-gray-700">Confirm new password</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        </div>
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Update password
        </button>
    </form>
</div>
