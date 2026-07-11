<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Roles</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Role' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Description (optional)</label>
                    <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('description') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                Create
            </button>
        </form>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Description</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Permissions</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Users</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($roles as $role)
                    <tr wire:key="role-{{ $role->id }}">
                        <td class="px-4 py-2 font-medium">
                            {{ $role->name }}
                            @if ($role->is_system)
                                <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400">System</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $role->description ?: '-' }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $role->users_count }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $role->is_active,
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => ! $role->is_active,
                            ])>
                                {{ $role->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('admin.roles.show', $role) }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Manage</a>
                            @unless ($role->is_system)
                                <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                                <button type="button" wire:click="toggleActive({{ $role->id }})"
                                    wire:confirm="{{ $role->is_active ? 'Disable this role? Every user holding it will immediately lose its permissions.' : 'Re-enable this role?' }}"
                                    class="text-xs {{ $role->is_active ? 'text-red-600 dark:text-red-400 hover:text-red-500' : 'text-green-600 hover:text-green-500' }}">
                                    {{ $role->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
