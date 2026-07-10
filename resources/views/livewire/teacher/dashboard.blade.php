<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        @if ($teacher->status->value === 'pending')
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                Awaiting first assignment
            </span>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 mb-8">
        <x-stat-card icon="book-open" label="My classes/subjects">{{ $assignments->count() }}</x-stat-card>
        <x-stat-card icon="users" label="Students in my classes">{{ $studentCount }}</x-stat-card>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-900">My assignments</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Term</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($assignments as $assignment)
                    <tr wire:key="assignment-{{ $assignment->id }}">
                        <td class="px-4 py-2 font-medium">{{ $assignment->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $assignment->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $assignment->term->name }}</td>
                        <td class="px-4 py-2 text-right space-x-3">
                            <a href="{{ route('teacher.attendance', $assignment->class_id) }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">Mark Attendance</a>
                            <a href="{{ route('teacher.grades', [$assignment->class_id, $assignment->subject_id]) }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">Enter Grades</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No subject/class assignments yet - an administrator needs to assign you first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
