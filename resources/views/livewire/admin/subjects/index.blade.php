<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Subjects</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Subject' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" wire:model="name" placeholder="Mathematics" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Code</label>
                    <input type="text" wire:model="code" placeholder="MATH1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('code') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
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
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Code</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($subjects as $subject)
                    <tr wire:key="subject-{{ $subject->id }}">
                        <td class="px-4 py-2 font-medium">{{ $subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $subject->code }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No subjects yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
