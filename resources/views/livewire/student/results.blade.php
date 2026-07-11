<div>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">My Results</h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Term</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($results as $result)
                    <tr wire:key="result-{{ $result->id }}">
                        <td class="px-4 py-2 font-medium">{{ $result->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->term->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->score }} / {{ $result->max_score }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No approved results yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
