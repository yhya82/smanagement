<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Security Events</h1>
        <select wire:model.live="event" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">All events</option>
            @foreach ($eventTypes as $eventOption)
                <option value="{{ $eventOption }}">{{ $eventOption }}</option>
            @endforeach
        </select>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Failed logins, account lockouts, and permission denials - a separate trail from the Audit Log since these aren't changes to a business record.</p>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">When</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Event</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Account</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">IP Address</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($events as $securityEvent)
                    <tr wire:key="event-{{ $securityEvent->id }}">
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $securityEvent->created_at->format('M j, Y g:ia') }}</td>
                        <td class="px-4 py-2 font-medium">{{ $securityEvent->event }}</td>
                        <td class="px-4 py-2">{{ $securityEvent->user?->name ?? $securityEvent->email ?? 'Unknown' }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $securityEvent->ip_address ?? '—' }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                            @if ($securityEvent->context)
                                {{ data_get($securityEvent->context, 'url') ?? data_get($securityEvent->context, 'message') ?? '' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No security events recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
</div>
