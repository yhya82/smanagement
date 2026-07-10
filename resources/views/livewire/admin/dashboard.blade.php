<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Dashboard</h1>

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
    </div>

    <div class="bg-white rounded-lg border border-gray-200 mb-8">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-900">Academic structure</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
            <a href="{{ route('admin.academic-years.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center hover:bg-gray-50">
                <x-icon name="calendar" class="size-5 text-gray-400" /> Academic Years
            </a>
            <a href="{{ route('admin.terms.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center hover:bg-gray-50">
                <x-icon name="clock" class="size-5 text-gray-400" /> Terms
            </a>
            <a href="{{ route('admin.grade-levels.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center hover:bg-gray-50">
                <x-icon name="academic-cap" class="size-5 text-gray-400" /> Grade Levels
            </a>
            <a href="{{ route('admin.subjects.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center hover:bg-gray-50">
                <x-icon name="book-open" class="size-5 text-gray-400" /> Subjects
            </a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-4 py-3 text-sm text-center hover:bg-gray-50">
                <x-icon name="rectangle-group" class="size-5 text-gray-400" /> Classes
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900">Applications awaiting review</h2>
            <a href="{{ route('admin.applications.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($pendingApplications as $application)
                <a href="{{ route('admin.applications.show', $application) }}" wire:navigate
                    class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50">
                    <span>{{ $application->first_name }} {{ $application->last_name }}</span>
                    <span class="text-gray-500 text-xs">{{ $application->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">No applications waiting for review.</p>
            @endforelse
        </div>
    </div>
</div>
