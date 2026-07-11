<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
        <a href="{{ route('registrar.applications.create') }}" wire:navigate
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            New Application
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <x-stat-card icon="clock" color="yellow" label="Pending review">{{ $counts['pending'] }}</x-stat-card>
        <x-stat-card icon="check-circle" color="green" label="Approved">{{ $counts['approved'] }}</x-stat-card>
        <x-stat-card icon="x-circle" color="red" label="Rejected">{{ $counts['rejected'] }}</x-stat-card>
    </div>

    <div class="grid grid-cols-2 gap-4 items-start">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Your recent applications</h2>
                <a href="{{ route('registrar.applications.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View all</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($recentApplications as $application)
                    <a href="{{ route('registrar.applications.show', $application) }}" wire:navigate
                        class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span>{{ $application->first_name }} {{ $application->last_name }}</span>
                        <span @class([
                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-400' => $application->status->value === 'pending',
                            'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $application->status->value === 'approved',
                            'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $application->status->value === 'rejected',
                        ])>
                            {{ ucfirst($application->status->value) }}
                        </span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No applications submitted yet.</p>
                @endforelse
            </div>
        </div>

        @include('partials.upcoming-calendar-events')
    </div>
</div>
