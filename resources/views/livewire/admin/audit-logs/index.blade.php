<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Audit Log</h1>
        <select wire:model.live="action" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">All actions</option>
            @foreach ($actions as $actionOption)
                <option value="{{ $actionOption }}">{{ $actionOption }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">When</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">User</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Action</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Record</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" x-data="{ open: false }">
                        <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                        <td class="px-4 py-2">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-2 font-medium">{{ $log->action }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                        <td class="px-4 py-2 text-right">
                            @if ($log->old_values || $log->new_values)
                                <button type="button" @click="open = !open" class="text-xs text-indigo-600 hover:text-indigo-500">
                                    <span x-show="!open">Show changes</span>
                                    <span x-show="open" x-cloak>Hide changes</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($log->old_values || $log->new_values)
                        <tr x-show="open" x-cloak wire:key="log-{{ $log->id }}-details">
                            <td colspan="5" class="px-4 py-3 bg-gray-50 text-xs font-mono">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-gray-500 mb-1">Before</p>
                                        <pre class="whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 mb-1">After</p>
                                        <pre class="whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No audit entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
