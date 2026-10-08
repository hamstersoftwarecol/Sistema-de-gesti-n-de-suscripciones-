@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'prefix' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $current = $type === 'password' ? null : old($key, $value instanceof \DateTimeInterface ? $value->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d') : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">{{ $prefix }}</span>
        @endif
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}" @required($required)
               {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($prefix ? ' pl-12' : '').($errors->has($key) ? ' border-red-500 focus:border-red-500 focus:ring-red-500' : '')]) }}>
    </div>
    @error($key)
        <p class="input-error">{{ $message }}</p>
    @enderror
    @if ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
