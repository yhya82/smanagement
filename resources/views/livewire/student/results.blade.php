<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">My Results</h1>
        <select wire:model.live="termId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">Select term...</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>
    </div>

    @if (! $termId)
        <p class="text-sm text-gray-500 dark:text-gray-400">Select a term to view your results.</p>
    @else
        @if ($ranking && $ranking->position !== null)
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex flex-wrap gap-6">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Position</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $ranking->position }}{{ $ranking->is_tied ? ' (tied)' : '' }} of {{ $classSize }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Overall average</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $ranking->average }}%</p>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Midterm</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($subjects as $row)
                        <tr wire:key="subject-{{ $row['subject']->id }}">
                            <td class="px-4 py-2 font-medium">{{ $row['subject']->name }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                {{ $row['midterm'] ? "{$row['midterm']->score} / {$row['midterm']->max_score}" : '-' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                {{ $row['final'] ? "{$row['final']->score} / {$row['final']->max_score}" : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No approved results for this term yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ranking?->remark)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Class teacher's remark</p>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $ranking->remark }}</p>
            </div>
        @endif
    @endif
</div>
