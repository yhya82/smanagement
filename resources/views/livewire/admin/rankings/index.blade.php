<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Term Rankings</h1>

    <form wire:submit="compute" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Compute rankings</h2>
        <p class="text-xs text-gray-500">
            Averages each student's approved subject scores and ranks every class that has approved results for the
            selected term. Run this once a term's grading is complete, before evaluating promotions.
        </p>
        @if ($computeResult)
            <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ $computeResult }}</div>
        @endif
        <div class="flex items-end gap-4">
            <div>
                <label class="block text-sm text-gray-700">Term</label>
                <select wire:model="termId" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Select...</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                </select>
                @error('termId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Compute Rankings
            </button>
        </div>
    </form>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-medium text-gray-900">Results</h2>
        <select wire:model.live="classId" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">All classes</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Position</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Student</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Average</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rankings as $ranking)
                    <tr wire:key="ranking-{{ $ranking->id }}">
                        <td class="px-4 py-2 font-medium">
                            {{ $ranking->position }}{{ $ranking->is_tied ? ' (tied)' : '' }}
                        </td>
                        <td class="px-4 py-2">{{ $ranking->student->first_name }} {{ $ranking->student->last_name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $ranking->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $ranking->average }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No rankings computed for this term yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
