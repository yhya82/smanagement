<div class="max-w-2xl">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">School Settings</h1>

    <div class="flex gap-4 border-b border-gray-200 dark:border-gray-700 mb-6 text-sm">
        <a href="{{ route('admin.settings.edit') }}" wire:navigate
            class="px-1 pb-2 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">General</a>
        <a href="{{ route('admin.settings.calendar') }}" wire:navigate
            class="px-1 pb-2 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-medium">School Calendar</a>
    </div>

    <div class="flex items-end gap-4 mb-6">
        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Term</label>
            <select wire:model.live="termId" class="mt-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                <option value="">Select...</option>
                @foreach ($terms as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($term)
            <p class="text-sm text-gray-500 dark:text-gray-400 pb-2">
                {{ $term->start_date->format('M j, Y') }} - {{ $term->end_date->format('M j, Y') }} ({{ $weeks }} weeks)
            </p>
        @endif
    </div>

    @if ($term)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Date</label>
                    <input type="date" wire:model="date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('date') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Title</label>
                    <input type="text" wire:model="title" placeholder="Independence Day" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('title') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Type</label>
                    <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="holiday">Public holiday</option>
                        <option value="event">Event</option>
                    </select>
                    @error('type') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Add
            </button>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Type</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($events as $event)
                        <tr wire:key="event-{{ $event->id }}">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $event->date->format('M j, Y') }}</td>
                            <td class="px-4 py-2 font-medium">{{ $event->title }}</td>
                            <td class="px-4 py-2">
                                <span @class([
                                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400' => $event->type->value === 'holiday',
                                    'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $event->type->value === 'event',
                                ])>
                                    {{ $event->type->value === 'holiday' ? 'Public holiday' : 'Event' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" wire:click="delete({{ $event->id }})" wire:confirm="Remove this calendar entry?"
                                    class="text-xs text-red-600 dark:text-red-400 hover:text-red-500">Remove</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No events or holidays for this term yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Select a term to view or manage its calendar.</p>
    @endif
</div>
