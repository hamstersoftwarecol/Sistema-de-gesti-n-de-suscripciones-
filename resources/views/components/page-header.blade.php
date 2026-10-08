@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <x-icon name="chevron-left" class="h-4 w-4" /> {{ __('Back') }}
            </a>
        @endif
        <h1 class="truncate text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
