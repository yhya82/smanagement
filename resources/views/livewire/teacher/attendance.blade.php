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
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    @php $lockedRecord = $lockedRecords->get($student->id); @endphp
                    <tr wire:key="student-{{ $student->id }}">
                        <td class="px-4 py-2 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
                        <td class="px-4 py-2">
                            @if ($lockedRecord)
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
                        <td class="px-4 py-2 text-right">
                            @if ($lockedRecord)
                                @if (in_array($lockedRecord->id, $pendingEditRequestRecordIds))
                                    <span class="text-xs text-yellow-700">Edit request pending</span>
                                @else
                                    <button type="button" wire:click="openEditRequest({{ $student->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-500">Request edit</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @if ($editingStudentId === $student->id)
                        <tr wire:key="student-{{ $student->id }}-edit-form">
                            <td colspan="3" class="px-4 py-3 bg-gray-50">
                                @if ($editRequestError)
                                    <div class="mb-3 rounded-md bg-red-50 border border-red-200 p-2 text-xs text-red-700">
                                        {{ $editRequestError }}
                                    </div>
                                @endif
                                <div class="flex items-end gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-700">Requested status</label>
                                        <select wire:model="requestedStatus" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                                            <option value="">Select...</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                            <option value="excused">Excused</option>
                                        </select>
                                        @error('requestedStatus') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-700">Reason</label>
                                        <input type="text" wire:model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        @error('reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="button" wire:click="submitEditRequest"
                                        class="bg-indigo-600 text-white text-sm font-medium px-3 py-1.5 rounded-md hover:bg-indigo-500">
                                        Submit
                                    </button>
                                    <button type="button" wire:click="cancelEditRequest"
                                        class="text-sm text-gray-500 hover:text-gray-700 px-2 py-1.5">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">No students in this class yet.</td>
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
