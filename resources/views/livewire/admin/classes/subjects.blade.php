<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $class->name }} - Subjects</h1>
        <a href="{{ route('admin.classes.index') }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Back to classes</a>
    </div>

    <form wire:submit="assign" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm text-gray-700 dark:text-gray-300">Subject</label>
                <select wire:model="subject_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    <option value="">Select...</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
                @error('subject_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex-1">
                <label class="block text-sm text-gray-700 dark:text-gray-300">Term</label>
                <select wire:model="term_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    <option value="">Select...</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }} ({{ $term->academicYear->name }})</option>
                    @endforeach
                </select>
                @error('term_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                Assign
            </button>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Term</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($assignments as $assignment)
                    <tr wire:key="assignment-{{ $assignment->id }}">
                        <td class="px-4 py-2 font-medium">{{ $assignment->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $assignment->term->name }}</td>
                        <td class="px-4 py-2 text-right">
                            <button type="button" wire:click="remove({{ $assignment->id }})"
                                wire:confirm="Remove this subject assignment?"
                                class="text-xs text-red-600 dark:text-red-400 hover:text-red-500">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No subjects assigned to this class yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
