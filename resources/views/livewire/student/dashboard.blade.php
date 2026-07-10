<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Welcome, {{ $student->first_name }}</h1>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <a href="{{ route('student.results') }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300">
            <p class="text-xs text-gray-500">Approved results</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $approvedResultsCount }}</p>
        </a>
        <a href="{{ route('student.attendance') }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300">
            <p class="text-xs text-gray-500">Present (last 10 days)</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $presentCount }}/{{ $recentAttendance->count() }}</p>
        </a>
        <a href="{{ route('student.notifications') }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300">
            <p class="text-xs text-gray-500">Unread notifications</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $unreadNotifications->count() }}</p>
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900">Recent notifications</h2>
            <a href="{{ route('student.notifications') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">View all</a>
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
