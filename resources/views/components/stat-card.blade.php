@props(['icon', 'label', 'color' => 'gray', 'href' => null])

@php
    $badgeClasses = match ($color) {
        'yellow' => 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        'green' => 'bg-green-50 dark:bg-green-500/10 text-green-600 dark:text-green-400',
        'red' => 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400',
        default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    };
    $valueClasses = match ($color) {
        'yellow' => 'text-yellow-600 dark:text-yellow-400',
        'green' => 'text-green-600 dark:text-green-400',
        'red' => 'text-red-600 dark:text-red-400',
        default => 'text-gray-900 dark:text-gray-100',
    };
@endphp

@if ($href)
<a href="{{ $href }}" wire:navigate class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 flex items-start gap-3">
@else
<div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700 flex items-start gap-3">
@endif
    <span class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg {{ $badgeClasses }}">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <span>
        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <span class="block text-2xl font-semibold {{ $valueClasses }} mt-1">{{ $slot }}</span>
    </span>
@if ($href)
</a>
@else
</div>
@endif
