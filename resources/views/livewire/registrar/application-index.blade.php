<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Applications</h1>
        <a href="{{ route('registrar.applications.create') }}" wire:navigate
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            New Application
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Guardians</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Documents</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($applications as $application)
                    <tr>
                        <td class="px-4 py-2">{{ $application->first_name }} {{ $application->last_name }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-yellow-100 text-yellow-800' => $application->status->value === 'pending',
                                'bg-green-100 text-green-800' => $application->status->value === 'approved',
                                'bg-red-100 text-red-800' => $application->status->value === 'rejected',
                            ])>
                                {{ ucfirst($application->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $application->guardians_count }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $application->documents_count }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $application->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('registrar.applications.show', $application) }}" wire:navigate
                                class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No applications yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applications->links() }}
    </div>
</div>
