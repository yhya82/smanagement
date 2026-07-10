<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Welcome, {{ $student->first_name }}</h1>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <x-stat-card icon="chart-bar" label="Approved results" :href="route('student.results')">
            {{ $approvedResultsCount }}
        </x-stat-card>
        <x-stat-card icon="check-circle" label="Present (last 10 days)" :href="route('student.attendance')">
            {{ $presentCount }}/{{ $recentAttendance->count() }}
        </x-stat-card>
        <x-stat-card icon="bell" label="Unread notifications" :href="route('notifications')">
            {{ $unreadNotifications->count() }}
        </x-stat-card>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900">Recent notifications</h2>
            <a href="{{ route('notifications') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($unreadNotifications as $notification)
                <div class="px-4 py-3 text-sm">
                    <p class="font-medium text-gray-900">{{ $notification->title }}</p>
                    <p class="text-gray-500">{{ $notification->message }}</p>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">No unread notifications.</p>
            @endforelse
        </div>
    </div>
</div>
