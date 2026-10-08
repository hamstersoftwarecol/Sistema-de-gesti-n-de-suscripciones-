@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3, 'hint' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' border-red-500' : '')]) }}>{{ old($key, $value) }}</textarea>
    @error($key)
        <p class="input-error">{{ $message }}</p>
    @enderror
    @if ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
