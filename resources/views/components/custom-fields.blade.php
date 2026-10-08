{{-- Renders the user-defined fields of an entity inside a form. --}}
@props(['model'])
@php
    $fields = $model::customFields();
@endphp
@if ($fields->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'card']) }}>
        <div class="card-header"><h2 class="card-title">{{ __('Additional information') }}</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    $name = "custom_fields[{$field->name}]";
                    $value = $model->exists ? $model->customFieldValue($field) : null;
                @endphp
                @switch($field->type)
                    @case('textarea')
                        <x-forms.textarea :name="$name" :label="$field->label" :value="$value" :required="$field->is_required" :placeholder="$field->placeholder" class="sm:col-span-2" />
                        @break
                    @case('select')
                        <x-forms.select :name="$name" :label="$field->label" :value="$value" :required="$field->is_required"
                                        :options="collect($field->options ?? [])->mapWithKeys(fn ($o) => [$o => $o])->all()" :placeholder="__('Select...')" />
                        @break
                    @case('checkbox')
                        <x-forms.checkbox :name="$name" :label="$field->label" :checked="(bool) $value" class="pt-6" />
                        @break
                    @default
                        <x-forms.input :name="$name" :label="$field->label" :value="$value" :required="$field->is_required"
                                       :type="$field->type" :placeholder="$field->placeholder" :step="$field->type === 'number' ? 'any' : null" />
                @endswitch
            @endforeach
        </div>
    </div>
@endif
