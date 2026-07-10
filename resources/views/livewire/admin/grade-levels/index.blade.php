<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Grade Levels</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Grade Level' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Name</label>
                    <input type="text" wire:model="name" placeholder="Secondary 7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Sort order</label>
                    <input type="number" wire:model="sort_order" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('sort_order') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                Create
            </button>
        </form>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">#</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Classes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($gradeLevels as $gradeLevel)
                    <tr wire:key="grade-{{ $gradeLevel->id }}">
                        <td class="px-4 py-2 text-gray-500">{{ $gradeLevel->sort_order }}</td>
                        <td class="px-4 py-2 font-medium">{{ $gradeLevel->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $gradeLevel->classes_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">No grade levels yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
