<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Grade Review</h1>
        <div class="flex items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Class</label>
                <select wire:model.live="classId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    <option value="">All classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Subject</label>
                <select wire:model.live="subjectId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Term</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Exam</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($results as $result)
                    <tr wire:key="result-{{ $result->id }}">
                        <td class="px-4 py-2 font-medium">{{ $result->student->first_name }} {{ $result->student->last_name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->term->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ ucfirst($result->exam_type->value) }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $result->score }} / {{ $result->max_score }}</td>
                        <td class="px-4 py-2 text-right space-x-3">
                            <button type="button" wire:click="approve({{ $result->id }})" class="text-xs text-green-600 hover:text-green-500">Approve</button>
                            <button type="button" wire:click="reject({{ $result->id }})" wire:confirm="Reject this result? The teacher can revise and resubmit it." class="text-xs text-red-600 dark:text-red-400 hover:text-red-500">Reject</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No grades awaiting review.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $results->links() }}
    </div>
</div>
