<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Users</h1>
        <a href="{{ route('admin.users.create') }}" wire:navigate
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            New User
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email"
            class="rounded-md border-gray-300 shadow-sm text-sm">
        <select wire:model.live="roleId" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Email</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Role</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-2 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 text-green-800' => $user->status->value === 'active',
                                'bg-gray-100 text-gray-600' => $user->status->value !== 'active',
                            ])>
                                {{ ucfirst($user->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
