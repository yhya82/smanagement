<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Audit Log</h1>
        <select wire:model.live="action" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">All actions</option>
            @foreach ($actions as $actionOption)
                <option value="{{ $actionOption }}">{{ $actionOption }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">When</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">User</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Action</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Record</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" x-data="{ open: false }">
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                        <td class="px-4 py-2">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-2 font-medium">{{ $log->action }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                        <td class="px-4 py-2 text-right">
                            @if ($log->old_values || $log->new_values)
                                <button type="button" @click="open = !open" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                                    <span x-show="!open">Show changes</span>
                                    <span x-show="open" x-cloak>Hide changes</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($log->old_values || $log->new_values)
                        @php
                            $changedFields = collect(array_keys((array) $log->old_values))
                                ->merge(array_keys((array) $log->new_values))
                                ->unique()
                                ->values();

                            $formatValue = function ($value) {
                                if ($value === null) {
                                    return '—';
                                }

                                return is_scalar($value) ? (string) $value : json_encode($value);
                            };
                        @endphp
                        <tr x-show="open" x-cloak wire:key="log-{{ $log->id }}-details">
                            <td colspan="5" class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-xs">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-gray-500 dark:text-gray-400">
                                            <th class="text-left font-medium pr-4 py-1">Field</th>
                                            <th class="text-left font-medium pr-4 py-1">Before</th>
                                            <th class="text-left font-medium py-1">After</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($changedFields as $field)
                                            <tr>
                                                <td class="pr-4 py-1 font-medium text-gray-700 dark:text-gray-300">{{ $field }}</td>
                                                <td class="pr-4 py-1 text-gray-600 dark:text-gray-400">{{ $formatValue(data_get($log->old_values, $field)) }}</td>
                                                <td class="py-1 text-gray-900 dark:text-gray-100">{{ $formatValue(data_get($log->new_values, $field)) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No audit entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
