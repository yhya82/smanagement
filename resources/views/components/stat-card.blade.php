@props(['icon', 'label', 'color' => 'gray', 'href' => null])

@php
    $badgeClasses = match ($color) {
        'yellow' => 'bg-yellow-50 text-yellow-600',
        'green' => 'bg-green-50 text-green-600',
        'red' => 'bg-red-50 text-red-600',
        default => 'bg-gray-100 text-gray-600',
    };
    $valueClasses = match ($color) {
        'yellow' => 'text-yellow-600',
        'green' => 'text-green-600',
        'red' => 'text-red-600',
        default => 'text-gray-900',
    };
@endphp

@if ($href)
<a href="{{ $href }}" wire:navigate class="bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-300 flex items-start gap-3">
@else
<div class="bg-white p-5 rounded-lg border border-gray-200 flex items-start gap-3">
@endif
    <span class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg {{ $badgeClasses }}">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <span>
        <span class="block text-xs text-gray-500">{{ $label }}</span>
        <span class="block text-2xl font-semibold {{ $valueClasses }} mt-1">{{ $slot }}</span>
    </span>
@if ($href)
</a>
@else
</div>
@endif
