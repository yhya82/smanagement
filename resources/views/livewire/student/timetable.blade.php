<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">My Timetable</h1>
        @if ($term)
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $term->name }}</span>
        @endif
    </div>

    @if (! $term)
        <p class="text-sm text-gray-500 dark:text-gray-400">No active term configured.</p>
    @elseif ($periods->isEmpty() || $entries->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Your class's timetable hasn't been set yet.</p>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Period</th>
                        @foreach ($days as $day)
                            <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($day) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($periods as $period)
                        <tr wire:key="period-row-{{ $period->id }}">
                            <td class="px-4 py-2 font-medium whitespace-nowrap">
                                {{ $period->name }}
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Illuminate\Support\Carbon::parse($period->start_time)->format('g:ia') }}-{{ \Illuminate\Support\Carbon::parse($period->end_time)->format('g:ia') }}
                                </div>
                            </td>
                            @foreach ($days as $day)
                                @php $entry = $entries->get("{$day}:{$period->id}"); @endphp
                                <td class="px-4 py-2 align-top">
                                    @if ($entry)
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->subject->name }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $entry->teacher()?->user->name ?? 'No teacher assigned' }}</div>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
