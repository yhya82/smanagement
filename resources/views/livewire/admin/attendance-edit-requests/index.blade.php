<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Attendance Edit Requests</h1>
        <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="">All</option>
        </select>
    </div>

    @if ($actionError)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            {{ $actionError }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Requested by</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Change</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Reason</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($requests as $request)
                    <tr wire:key="edit-request-{{ $request->id }}">
                        <td class="px-4 py-2 font-medium">
                            {{ $request->attendanceRecord->student->first_name }} {{ $request->attendanceRecord->student->last_name }}
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $request->attendanceRecord->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $request->attendanceRecord->date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $request->requestedBy->name }}</td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ ucfirst($request->attendanceRecord->status->value) }} &rarr; {{ ucfirst($request->requested_status->value) }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 max-w-xs truncate" title="{{ $request->reason }}">{{ $request->reason }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-yellow-100 text-yellow-800' => $request->status->value === 'pending',
                                'bg-green-100 text-green-800' => $request->status->value === 'approved',
                                'bg-red-100 text-red-800' => $request->status->value === 'rejected',
                            ])>
                                {{ ucfirst($request->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            @if ($request->status->value === 'pending')
                                <button type="button" wire:click="approve({{ $request->id }})"
                                    wire:confirm="Approve this edit request?"
                                    class="text-xs text-green-600 hover:text-green-500">Approve</button>
                                <span class="text-gray-300 mx-1">|</span>
                                <button type="button" wire:click="reject({{ $request->id }})"
                                    wire:confirm="Reject this edit request?"
                                    class="text-xs text-red-600 hover:text-red-500">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No edit requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
