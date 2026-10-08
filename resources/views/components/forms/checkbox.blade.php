@props(['name', 'label', 'checked' => false, 'hint' => null, 'value' => '1'])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $isChecked = old('_token') !== null ? (bool) old($key) : (bool) $checked;
@endphp
<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $id }}" class="inline-flex cursor-pointer items-start gap-2">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
               {{ $attributes->except(['class', 'id'])->merge(['class' => 'checkbox mt-0.5']) }}>
        <span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
            @if ($hint)
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
            @endif
        </span>
    </label>
    @error($key)
        <p class="input-error">{{ $message }}</p>
    @enderror
</div>
