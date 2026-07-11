<div>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Students</h1>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or student no."
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
        <select wire:model.live="status" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="transferred">Transferred</option>
            <option value="graduated">Graduated</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
        <select wire:model.live="classId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">All classes</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Student no.</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($students as $student)
                    <tr wire:key="student-{{ $student->id }}">
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $student->student_no }}</td>
                        <td class="px-4 py-2 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $student->currentClass?->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $student->status->value === 'active',
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => in_array($student->status->value, ['inactive', 'transferred']),
                                'bg-blue-100 dark:bg-blue-500/10 text-blue-800 dark:text-blue-400' => $student->status->value === 'graduated',
                                'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $student->status->value === 'withdrawn',
                            ])>
                                {{ ucfirst($student->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.students.show', $student) }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>
</div>
