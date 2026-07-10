<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Academic Years</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Academic Year' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Name</label>
                    <input type="text" wire:model="name" placeholder="2026/2027" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Start date</label>
                    <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('start_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">End date</label>
                    <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Dates</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Classes</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($academicYears as $year)
                    <tr wire:key="year-{{ $year->id }}">
                        <td class="px-4 py-2 font-medium">{{ $year->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $year->start_date->format('M j, Y') }} - {{ $year->end_date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $year->classes_count }}</td>
                        <td class="px-4 py-2">
                            @if ($year->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @unless ($year->is_active)
                                <button type="button" wire:click="activate({{ $year->id }})"
                                    wire:confirm="Activate {{ $year->name }}? This will deactivate the current active year."
                                    class="text-xs text-indigo-600 hover:text-indigo-500">
                                    Activate
                                </button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No academic years yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
