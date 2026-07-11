<div class="max-w-lg">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">My Profile</h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-6">
        <div class="flex items-center gap-4">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" class="w-16 h-16 rounded-full object-cover" alt="{{ $user->name }}">
            @else
                <span class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 flex items-center justify-center text-lg font-medium">
                    {{ $user->initials() }}
                </span>
            @endif
            <div>
                <p class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm border-t border-gray-100 dark:border-gray-700 pt-4">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Role</dt>
                <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd>
                    <span @class([
                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                        'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $user->status->value === 'active',
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $user->status->value !== 'active',
                    ])>
                        {{ ucfirst($user->status->value) }}
                    </span>
                </dd>
            </div>
            @if ($user->teacher)
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Employee No.</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $user->teacher->employee_no }}</dd>
                </div>
            @endif
            @if ($user->student)
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Student No.</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $user->student->student_no }}</dd>
                </div>
            @endif
        </dl>
    </div>
</div>
