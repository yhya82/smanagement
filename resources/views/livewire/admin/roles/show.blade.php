<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">
            {{ $role->name }}
            @if ($role->is_system)
                <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 align-middle">System</span>
            @endif
        </h1>
        <a href="{{ route('admin.roles.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">Back to roles</a>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4 mb-6">
        <h2 class="text-sm font-medium text-gray-900">Details</h2>
        <form wire:submit="updateDetails" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-700">Name</label>
                <input type="text" wire:model="name" @disabled($role->is_system)
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-50 disabled:text-gray-500">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                @if ($role->is_system)
                    <p class="text-xs text-gray-500 mt-1">System role names can't be changed - the app matches routes and middleware against them.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm text-gray-700">Description</label>
                <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                Save
            </button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Permissions</h2>
        <form wire:submit="savePermissions" class="space-y-4">
            <div class="grid grid-cols-2 gap-2 max-h-96 overflow-y-auto">
                @foreach ($permissions as $permission)
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" wire:model="selectedPermissionIds" value="{{ $permission->id }}"
                            class="mt-0.5 rounded border-gray-300">
                        <span>
                            <span class="block font-medium text-gray-900">{{ $permission->key }}</span>
                            <span class="block text-xs text-gray-500">{{ $permission->description }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Save permissions
            </button>
        </form>
    </div>
</div>
