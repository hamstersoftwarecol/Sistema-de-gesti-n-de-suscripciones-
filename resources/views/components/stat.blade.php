@props(['label', 'value', 'icon' => 'chart', 'hint' => null, 'trend' => null, 'color' => 'primary'])
@php
    $iconColors = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
        'green' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        'blue' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card p-4 sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-1 break-words text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $value }}</p>
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconColors[$color] ?? $iconColors['primary'] }}">
            <x-icon :name="$icon" />
        </span>
    </div>
    @if ($hint !== null || $trend !== null)
        <div class="mt-3 flex items-center gap-2 text-xs">
            @if ($trend !== null)
                <span @class(['inline-flex items-center gap-0.5 font-semibold', 'text-emerald-600 dark:text-emerald-400' => $trend >= 0, 'text-red-600 dark:text-red-400' => $trend < 0])>
                    <x-icon :name="$trend >= 0 ? 'trending-up' : 'trending-down'" class="h-3.5 w-3.5" />
                    {{ $trend >= 0 ? '+' : '' }}{{ $trend }}%
                </span>
            @endif
            @if ($hint)
                <span class="truncate text-gray-500 dark:text-gray-400">{{ $hint }}</span>
            @endif
        </div>
    @endif
</div>
