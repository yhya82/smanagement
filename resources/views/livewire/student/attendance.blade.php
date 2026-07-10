<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">My Attendance</h1>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="px-4 py-2">{{ $record->date->format('M j, Y') }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 text-green-800' => $record->status->value === 'present',
                                'bg-red-100 text-red-800' => $record->status->value === 'absent',
                                'bg-yellow-100 text-yellow-800' => $record->status->value === 'late',
                                'bg-gray-100 text-gray-600' => $record->status->value === 'excused',
                            ])>
                                {{ ucfirst($record->status->value) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">No attendance records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
