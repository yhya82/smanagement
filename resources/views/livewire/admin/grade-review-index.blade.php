<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Grade Review</h1>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Term</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($results as $result)
                    <tr wire:key="result-{{ $result->id }}">
                        <td class="px-4 py-2 font-medium">{{ $result->student->first_name }} {{ $result->student->last_name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $result->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $result->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $result->term->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $result->score }} / {{ $result->max_score }}</td>
                        <td class="px-4 py-2 text-right space-x-3">
                            <button type="button" wire:click="approve({{ $result->id }})" class="text-xs text-green-600 hover:text-green-500">Approve</button>
                            <button type="button" wire:click="reject({{ $result->id }})" wire:confirm="Reject this result? The teacher can revise and resubmit it." class="text-xs text-red-600 hover:text-red-500">Reject</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No grades awaiting review.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $results->links() }}
    </div>
</div>
