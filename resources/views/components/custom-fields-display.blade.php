{{-- Read-only list of the custom field values of a record. --}}
@props(['model'])
@php
    $fields = $model::customFields();
@endphp
@if ($fields->isNotEmpty())
    <dl {{ $attributes->merge(['class' => 'divide-y divide-gray-100 dark:divide-gray-800']) }}>
        @foreach ($fields as $field)
            <div class="dl-row">
                <dt>{{ $field->label }}</dt>
                <dd>{{ $field->displayValue($model->customFieldValue($field)) }}</dd>
            </div>
        @endforeach
    </dl>
@endif
