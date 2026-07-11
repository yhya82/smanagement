<div>
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Timetable - {{ $class->name }}</h1>
        <a href="{{ route('admin.classes.index') }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Back to classes</a>
    </div>

    <div class="flex items-center justify-end gap-3 mb-6">
        <select wire:model.live="termId" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            <option value="">Select term...</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="generate" wire:loading.attr="disabled"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500 disabled:opacity-50">
            Auto-generate
        </button>
    </div>

    @if ($generateResult)
        <div class="mb-4 text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-md px-4 py-2">
            {{ $generateResult }}
        </div>
    @endif

    @if ($slotError)
        <div class="mb-4 text-sm bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-md px-4 py-2">
            {{ $slotError }}
        </div>
    @endif

    @if (! $termId)
        <p class="text-sm text-gray-500 dark:text-gray-400">Select a term to view or build this class's timetable.</p>
    @elseif ($periods->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            No periods configured yet. <a href="{{ route('admin.periods.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 underline">Add periods first</a>.
        </p>
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
                                @php $slotKey = "{$day}:{$period->id}"; $entry = $entries->get($slotKey); @endphp
                                <td class="px-4 py-2 align-top" wire:key="cell-{{ $slotKey }}">
                                    @if ($editingSlot === $slotKey)
                                        <div class="space-y-2 min-w-[9rem]">
                                            <select wire:model="editingSubjectId" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-xs">
                                                <option value="">- Empty -</option>
                                                @foreach ($classSubjects as $subject)
                                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="flex gap-2">
                                                <button type="button" wire:click="saveSlot" class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-500">Save</button>
                                                <button type="button" wire:click="cancelSlot" class="text-xs bg-gray-200 dark:bg-gray-600 dark:text-gray-100 px-2 py-1 rounded hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" wire:click="openSlot('{{ $day }}', {{ $period->id }})"
                                            class="w-full text-left rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            @if ($entry)
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->subject->name }}</div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $teacherMap->get("{$class->id}:{$entry->subject_id}")?->user->name ?? 'No teacher assigned' }}</div>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                            @endif
                                        </button>
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
