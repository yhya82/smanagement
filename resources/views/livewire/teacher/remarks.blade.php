<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Term Remarks - {{ $class->name }}</h1>
        <select wire:model.live="termId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">Select term...</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($statusMessage)
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-3 text-sm text-green-700 dark:text-green-400">
            {{ $statusMessage }}
        </div>
    @endif

    @if (! $termId)
        <p class="text-sm text-gray-500 dark:text-gray-400">Select a term to write remarks.</p>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($students as $student)
                <div class="p-4">
                    <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ $student->first_name }} {{ $student->last_name }}</label>
                    <textarea wire:model="remarks.{{ $student->id }}" rows="2"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                        placeholder="e.g. Shows great improvement in class participation this term."></textarea>
                </div>
            @empty
                <p class="p-4 text-center text-gray-500 dark:text-gray-400">No students in this class yet.</p>
            @endforelse
        </div>

        @if ($students->isNotEmpty())
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="mt-4 bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Save Remarks
            </button>
        @endif
    @endif
</div>
