<div>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Dashboard</h1>

    <div class="grid grid-cols-4 gap-4 mb-8">
        <x-stat-card icon="clock" color="yellow" label="Pending applications" :href="route('admin.applications.index')">
            {{ $counts['pending_applications'] }}
        </x-stat-card>
        <x-stat-card icon="users" label="Active students" :href="route('admin.students.index')">
            {{ $counts['active_students'] }}
        </x-stat-card>
        <x-stat-card icon="user-group" label="Teachers" :href="route('admin.teachers.index')">
            {{ $counts['teachers'] }}
        </x-stat-card>
        <x-stat-card icon="rectangle-group" label="Classes" :href="route('admin.classes.index')">
            {{ $counts['classes'] }}
        </x-stat-card>
        <x-stat-card icon="pencil-square" color="yellow" label="Pending attendance edit requests" :href="route('admin.attendance-edit-requests.index')">
            {{ $counts['pending_attendance_edit_requests'] }}
        </x-stat-card>
        <x-stat-card icon="arrow-path" color="yellow" label="Pending promotions" :href="route('admin.promotions.index')">
            {{ $counts['pending_promotions'] }}
        </x-stat-card>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-8">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Academic structure</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100 dark:divide-gray-700">
            <a href="{{ route('admin.academic-years.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-icon name="calendar" class="size-5 text-gray-400" /> Academic Years
            </a>
            <a href="{{ route('admin.terms.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-icon name="clock" class="size-5 text-gray-400" /> Terms
            </a>
            <a href="{{ route('admin.grade-levels.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-icon name="academic-cap" class="size-5 text-gray-400" /> Grade Levels
            </a>
            <a href="{{ route('admin.subjects.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-icon name="book-open" class="size-5 text-gray-400" /> Subjects
            </a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <x-icon name="rectangle-group" class="size-5 text-gray-400" /> Classes
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Applications awaiting review</h2>
            <a href="{{ route('admin.applications.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($pendingApplications as $application)
                <a href="{{ route('admin.applications.show', $application) }}" wire:navigate
                    class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <span>{{ $application->first_name }} {{ $application->last_name }}</span>
                    <span class="text-gray-500 dark:text-gray-400 text-xs">{{ $application->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No applications waiting for review.</p>
            @endforelse
        </div>
    </div>
</div>
