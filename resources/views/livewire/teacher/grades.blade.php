<div class="max-w-2xl">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
        Grades - {{ $subject->name }} ({{ $class->name }})
    </h1>

    @if (! $term)
        <div class="rounded-md bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-200 dark:border-yellow-500/30 p-3 text-sm text-yellow-700 dark:text-yellow-400">
            No active term - an administrator needs to activate one before grades can be entered.
        </div>
    @else
        @if ($statusMessage)
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-3 text-sm text-green-700 dark:text-green-400">
                {{ $statusMessage }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Student</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Score</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Max score</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($students as $student)
                        @php $status = $statuses[$student->id] ?? null; @endphp
                        <tr wire:key="student-{{ $student->id }}">
                            <td class="px-4 py-2 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td class="px-4 py-2">
                                <input type="number" step="0.01" wire:model="entries.{{ $student->id }}.score"
                                    @disabled($status && $status->value !== 'draft')
                                    class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm disabled:bg-gray-100">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" step="0.01" wire:model="entries.{{ $student->id }}.max_score"
                                    @disabled($status && $status->value !== 'draft')
                                    class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm disabled:bg-gray-100">
                            </td>
                            <td class="px-4 py-2">
                                @if ($status)
                                    <span @class([
                                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $status->value === 'draft',
                                        'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-400' => $status->value === 'submitted',
                                        'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $status->value === 'approved',
                                        'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $status->value === 'rejected',
                                    ])>
                                        {{ ucfirst($status->value) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Not entered</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No students in this class yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->isNotEmpty())
            <div class="mt-4 flex gap-3">
                <button type="button" wire:click="saveDrafts" wire:loading.attr="disabled"
                    class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                    Save Drafts
                </button>
                <button type="button" wire:click="submitAll" wire:loading.attr="disabled"
                    wire:confirm="Submit all draft scores for admin approval? You won't be able to edit them until a decision is made."
                    class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                    Submit for Approval
                </button>
            </div>
        @endif
    @endif
</div>
