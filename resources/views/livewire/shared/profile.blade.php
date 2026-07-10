<div class="max-w-lg">
    <h1 class="text-xl font-semibold text-gray-900 mb-6">My Profile</h1>

    <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-6">
        <div class="flex items-center gap-4">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" class="w-16 h-16 rounded-full object-cover" alt="{{ $user->name }}">
            @else
                <span class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-medium">
                    {{ $user->initials() }}
                </span>
            @endif
            <div>
                <p class="text-base font-medium text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm border-t border-gray-100 pt-4">
            <div>
                <dt class="text-gray-500">Role</dt>
                <dd class="text-gray-900 font-medium">{{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    <span @class([
                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                        'bg-green-100 text-green-800' => $user->status->value === 'active',
                        'bg-gray-100 text-gray-600' => $user->status->value !== 'active',
                    ])>
                        {{ ucfirst($user->status->value) }}
                    </span>
                </dd>
            </div>
            @if ($user->teacher)
                <div>
                    <dt class="text-gray-500">Employee No.</dt>
                    <dd class="text-gray-900 font-medium">{{ $user->teacher->employee_no }}</dd>
                </div>
            @endif
            @if ($user->student)
                <div>
                    <dt class="text-gray-500">Student No.</dt>
                    <dd class="text-gray-900 font-medium">{{ $user->student->student_no }}</dd>
                </div>
            @endif
        </dl>
    </div>
</div>
