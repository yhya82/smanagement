@props(['href', 'active' => false])
<a href="{{ $href }}" wire:navigate x-on:click="sidebarOpen = false"
    @class([
        'flex items-center px-3 py-2 rounded-md text-sm font-medium',
        'bg-indigo-50 text-indigo-700' => $active,
        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
    ])>
    {{ $slot }}
</a>
