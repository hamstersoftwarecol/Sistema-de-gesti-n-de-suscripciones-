@props(['name', 'label' => null, 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'hint' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $current = (string) old($key, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' border-red-500' : '')]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current !== '' && $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($key)
        <p class="input-error">{{ $message }}</p>
    @enderror
    @if ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
