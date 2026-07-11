<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">School Calendar</h2>
    </div>
    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($upcomingEvents as $event)
            <li class="px-4 py-3 flex items-center justify-between text-sm">
                <div>
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $event->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $event->date->format('l, M j, Y') }}</p>
                </div>
                <span @class([
                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium shrink-0',
                    'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $event->type->value === 'holiday',
                    'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $event->type->value === 'event',
                ])>
                    {{ $event->type->value === 'holiday' ? 'Public holiday' : 'Event' }}
                </span>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No upcoming events or holidays.</li>
        @endforelse
    </ul>
</div>
