<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
        @if ($teacher->status->value === 'pending')
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-400">
                Awaiting first assignment
            </span>
        @endif
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <x-stat-card icon="book-open" label="My classes/subjects">{{ $assignments->count() }}</x-stat-card>
        <x-stat-card icon="users" label="Students in my classes">{{ $studentCount }}</x-stat-card>
        <x-stat-card icon="pencil-square" color="yellow" label="Pending attendance edit requests">{{ $pendingEditRequests }}</x-stat-card>
    </div>

    @if ($homeroomClass)
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <p class="text-sm text-gray-700 dark:text-gray-300">You're the homeroom teacher for <span class="font-medium text-gray-900 dark:text-gray-100">{{ $homeroomClass->name }}</span>.</p>
            <a href="{{ route('teacher.remarks', $homeroomClass) }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Write Term Remarks</a>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">My assignments</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subject</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Term</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($assignments as $assignment)
                    <tr wire:key="assignment-{{ $assignment->id }}">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $assignment->subject->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $assignment->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $assignment->term->name }}</td>
                        <td class="px-4 py-2 text-right space-x-3">
                            <a href="{{ route('teacher.attendance', $assignment->class_id) }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Mark Attendance</a>
                            <a href="{{ route('teacher.grades', [$assignment->class_id, $assignment->subject_id]) }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Enter Grades</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No subject/class assignments yet - an administrator needs to assign you first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        @include('partials.upcoming-calendar-events')
    </div>
</div>
