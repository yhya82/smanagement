<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Dashboard</h1>

    <div class="grid grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.applications.index') }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300">
            <p class="text-xs text-gray-500">Pending applications</p>
            <p class="text-2xl font-semibold text-yellow-600 mt-1">{{ $counts['pending_applications'] }}</p>
        </a>
        <div class="bg-white p-5 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500">Active students</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $counts['active_students'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500">Teachers</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $counts['teachers'] }}</p>
        </div>
        <a href="{{ route('admin.classes.index') }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300">
            <p class="text-xs text-gray-500">Classes</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $counts['classes'] }}</p>
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 mb-8">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-900">Academic structure</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
            <a href="{{ route('admin.academic-years.index') }}" wire:navigate class="px-4 py-3 text-sm text-center hover:bg-gray-50">Academic Years</a>
            <a href="{{ route('admin.terms.index') }}" wire:navigate class="px-4 py-3 text-sm text-center hover:bg-gray-50">Terms</a>
            <a href="{{ route('admin.grade-levels.index') }}" wire:navigate class="px-4 py-3 text-sm text-center hover:bg-gray-50">Grade Levels</a>
            <a href="{{ route('admin.subjects.index') }}" wire:navigate class="px-4 py-3 text-sm text-center hover:bg-gray-50">Subjects</a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="px-4 py-3 text-sm text-center hover:bg-gray-50">Classes</a>
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
