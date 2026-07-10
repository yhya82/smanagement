<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Application Review</h1>

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
                        <td class="px-4 py-2 text-gray-500">{{ $application->guardians_count }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $application->documents_count }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $application->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.applications.show', $application) }}" wire:navigate
                                class="text-indigo-600 hover:text-indigo-500">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No pending applications.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applications->links() }}
    </div>
</div>
