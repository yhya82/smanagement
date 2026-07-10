<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        <a href="{{ route('registrar.applications.create') }}" wire:navigate
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            New Application
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500">Pending review</p>
            <p class="text-2xl font-semibold text-yellow-600 mt-1">{{ $counts['pending'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500">Approved</p>
            <p class="text-2xl font-semibold text-green-600 mt-1">{{ $counts['approved'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500">Rejected</p>
            <p class="text-2xl font-semibold text-red-600 mt-1">{{ $counts['rejected'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-900">Your recent applications</h2>
            <a href="{{ route('registrar.applications.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($recentApplications as $application)
                <a href="{{ route('registrar.applications.show', $application) }}" wire:navigate
                    class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50">
                    <span>{{ $application->first_name }} {{ $application->last_name }}</span>
                    <span @class([
                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                        'bg-yellow-100 text-yellow-800' => $application->status->value === 'pending',
                        'bg-green-100 text-green-800' => $application->status->value === 'approved',
                        'bg-red-100 text-red-800' => $application->status->value === 'rejected',
                    ])>
                        {{ ucfirst($application->status->value) }}
                    </span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">No applications submitted yet.</p>
            @endforelse
        </div>
    </div>
</div>
