@props(['message' => null, 'icon' => 'info'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $message ?? __('No records found.') }}</p>
    @if (isset($slot) && trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
