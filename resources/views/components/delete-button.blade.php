@props(['action', 'confirm' => null, 'label' => null, 'icon' => true, 'size' => 'sm'])
<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm ?? __('Are you sure? This action cannot be undone.')))">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => $label ? 'btn-danger '.($size === 'sm' ? 'btn-sm' : '') : 'btn-icon h-8 w-8 hover:text-red-600 dark:hover:text-red-400']) }} title="{{ __('Delete') }}">
        @if ($icon) <x-icon name="trash" class="h-4 w-4" /> @endif
        {{ $label }}
    </button>
</form>
