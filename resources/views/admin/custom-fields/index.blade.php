<x-app-layout :title="__('Custom fields')">
    <x-page-header :title="__('Custom fields')" :subtitle="__('Add your own fields to customers, subscriptions, invoices, products, suppliers and sellers.')">
        <x-slot:actions>
            <a href="{{ route('admin.custom-fields.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New field') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @foreach (\App\Models\CustomField::entityOptions() as $entity => $label)
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ $label }}</h2>
                    <a href="{{ route('admin.custom-fields.create', ['entity' => $entity]) }}" class="btn-ghost btn-sm"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add') }}</a>
                </div>
                @if (($groups[$entity] ?? collect())->isEmpty())
                    <p class="px-6 py-4 text-sm text-gray-500">{{ __('No custom fields.') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>{{ __('Label') }}</th><th>{{ __('Key') }}</th><th>{{ __('Type') }}</th><th>{{ __('Required') }}</th><th>{{ __('In table') }}</th><th>{{ __('Values') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($groups[$entity] as $field)
                                    <tr>
                                        <td class="font-medium">{{ $field->label }}</td>
                                        <td class="font-mono text-xs">{{ $field->name }}</td>
                                        <td>{{ \App\Models\CustomField::typeOptions()[$field->type] ?? $field->type }}</td>
                                        <td>{{ $field->is_required ? __('Yes') : __('No') }}</td>
                                        <td>{{ $field->show_in_table ? __('Yes') : __('No') }}</td>
                                        <td>{{ $field->values_count }}</td>
                                        <td><x-status :value="(bool) $field->is_active" type="bool" /></td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.custom-fields.edit', $field) }}" class="btn-icon h-8 w-8"><x-icon name="pencil" class="h-4 w-4" /></a>
                                            <x-delete-button :action="route('admin.custom-fields.destroy', $field)" :confirm="__('Delete the field and all its stored values?')" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
