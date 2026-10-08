<x-app-layout :title="$field->exists ? __('Edit field') : __('New field')">
    <x-page-header :title="$field->exists ? __('Edit field') : __('New custom field')" :back="route('admin.custom-fields.index')" />

    <form method="POST" action="{{ $field->exists ? route('admin.custom-fields.update', $field) : route('admin.custom-fields.store') }}" class="card max-w-3xl" x-data="{ type: @js(old('type', $field->type)) }">
        @csrf
        @if ($field->exists) @method('PUT') @endif
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-forms.select name="entity" :label="__('Applies to')" :options="\App\Models\CustomField::entityOptions()" :value="$field->entity" required />
            <div>
                <label class="label" for="type">{{ __('Field type') }}</label>
                <select id="type" name="type" class="input" x-model="type">
                    @foreach (\App\Models\CustomField::typeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-forms.input name="label" :label="__('Label')" :value="$field->label" required />
            <x-forms.input name="name" :label="__('Internal key')" :value="$field->name" :hint="__('Lowercase letters, numbers and underscores. Generated from the label if empty.')" />
            <div class="sm:col-span-2" x-show="type === 'select'" x-cloak>
                <x-forms.textarea name="options_text" :label="__('Options (one per line)')" :value="implode(PHP_EOL, $field->options ?? [])" rows="5" />
            </div>
            <x-forms.input name="placeholder" :label="__('Placeholder')" :value="$field->placeholder" />
            <x-forms.input name="sort_order" type="number" min="0" :label="__('Order')" :value="$field->sort_order ?? 0" />
            <div class="flex flex-wrap gap-6 sm:col-span-2">
                <x-forms.checkbox name="is_required" :label="__('Required')" :checked="$field->is_required" />
                <x-forms.checkbox name="show_in_table" :label="__('Show as a column in lists')" :checked="$field->show_in_table" />
                <x-forms.checkbox name="is_active" :label="__('Active')" :checked="$field->is_active" />
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-800">
            <a href="{{ route('admin.custom-fields.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
