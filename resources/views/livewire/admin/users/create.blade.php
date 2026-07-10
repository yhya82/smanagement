<div class="max-w-lg">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">New User</h1>
        <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Back to users</a>
    </div>

    @if ($temporaryPassword)
        <div class="mb-6 rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            <p class="font-medium">User created.</p>
            <p class="mt-1">Temporary password: <code class="bg-white px-1.5 py-0.5 rounded border border-green-200 font-mono">{{ $temporaryPassword }}</code></p>
            <p class="mt-1 text-green-700">Share this with them directly - it will not be shown again. They'll be asked to set their own password on first login.</p>
        </div>
    @endif

    <form wire:submit="create" class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
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
            <select wire:model="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">Select...</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Create user
        </button>
    </form>
</div>
