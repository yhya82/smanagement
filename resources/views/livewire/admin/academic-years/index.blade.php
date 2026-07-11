<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Academic Years</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Academic Year' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" wire:model="name" placeholder="2026/2027" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Start date</label>
                    <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('start_date') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">End date</label>
                    <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('end_date') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
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
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Dates</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Classes</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($academicYears as $year)
                    <tr wire:key="year-{{ $year->id }}">
                        <td class="px-4 py-2 font-medium">{{ $year->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $year->start_date->format('M j, Y') }} - {{ $year->end_date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $year->classes_count }}</td>
                        <td class="px-4 py-2">
                            @if ($year->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400">Active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @unless ($year->is_active)
                                <button type="button" wire:click="activate({{ $year->id }})"
                                    wire:confirm="Activate {{ $year->name }}? This will deactivate the current active year."
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                                    Activate
                                </button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No academic years yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
