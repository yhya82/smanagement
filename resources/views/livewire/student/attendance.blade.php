<div>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">My Attendance</h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="px-4 py-2">{{ $record->date->format('M j, Y') }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $record->status->value === 'present',
                                'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $record->status->value === 'absent',
                                'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-400' => $record->status->value === 'late',
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $record->status->value === 'excused',
                            ])>
                                {{ ucfirst($record->status->value) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No attendance records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
