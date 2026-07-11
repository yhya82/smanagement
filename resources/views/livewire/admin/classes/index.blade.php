<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Classes</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Class' }}
        </button>
    </div>

    @if ($classTeacherError)
        <div class="mb-6 rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">
            {{ $classTeacherError }}
        </div>
    @endif

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Grade level</label>
                    <select wire:model="grade_level_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                        @endforeach
                    </select>
                    @error('grade_level_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Academic year</label>
                    <select wire:model="academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" wire:model="name" placeholder="Blue Stream" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Capacity (optional)</label>
                    <input type="number" wire:model="capacity" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('capacity') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                Create
            </button>
        </form>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Grade level</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Academic year</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Capacity</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Subjects</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Class teacher</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($classes as $class)
                    @php $isFull = $class->capacity !== null && $class->students_count >= $class->capacity; @endphp
                    <tr wire:key="class-{{ $class->id }}">
                        <td class="px-4 py-2 font-medium">{{ $class->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $class->gradeLevel->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $class->academicYear->name }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $isFull,
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => ! $isFull,
                            ])>
                                {{ $class->students_count }}{{ $class->capacity ? '/'.$class->capacity : '' }}
                            </span>
                            @if ($isFull)
                                <span class="text-xs text-red-600 dark:text-red-400 ml-1">Full</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $class->class_subjects_count }}</td>
                        <td class="px-4 py-2">
                            <select wire:change="assignClassTeacher({{ $class->id }}, $event.target.value)"
                                class="text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm">
                                <option value="">No class teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected($class->homeroom_teacher_id === $teacher->id)>
                                        {{ $teacher->user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('admin.classes.subjects', $class) }}" wire:navigate
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Subjects</a>
                            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                            <a href="{{ route('admin.classes.add-student', $class) }}" wire:navigate
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Add student</a>
                            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                            <a href="{{ route('admin.classes.import', $class) }}" wire:navigate
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Import</a>
                            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                            <a href="{{ route('admin.classes.timetable', $class) }}" wire:navigate
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Timetable</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No classes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
