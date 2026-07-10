<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Attendance - {{ $class->name }}</h1>
        <input type="date" wire:model.live="date" class="rounded-md border-gray-300 shadow-sm text-sm">
    </div>

    @if ($statusMessage)
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <tr wire:key="student-{{ $student->id }}">
                        <td class="px-4 py-2 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
                        <td class="px-4 py-2">
                            @if (in_array($student->id, $lockedStudentIds))
                                <span class="text-xs text-gray-500">
                                    {{ ucfirst($statuses[$student->id]) }} (locked - past the 7-day edit window)
                                </span>
                            @else
                                <select wire:model="statuses.{{ $student->id }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">No students in this class yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($students->isNotEmpty())
        <button type="button" wire:click="save" wire:loading.attr="disabled"
            class="mt-4 bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Save Attendance
        </button>
    @endif
</div>
