@props(['href', 'active' => false, 'icon' => null])
<a href="{{ $href }}" wire:navigate x-on:click="sidebarOpen = false"
    @class([
        'flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium',
        'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400' => $active,
        'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' => ! $active,
    ])>
    @if ($icon)
        <x-icon :name="$icon" class="size-5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</a>
