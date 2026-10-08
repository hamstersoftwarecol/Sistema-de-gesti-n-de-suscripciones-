<x-app-layout :title="$supplier->exists ? __('Edit supplier') : __('New supplier')">
    <x-page-header :title="$supplier->exists ? __('Edit supplier') : __('New supplier')" :back="$supplier->exists ? route('suppliers.show', $supplier) : route('suppliers.index')" />

    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="space-y-6">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-forms.input name="name" :label="__('Company name')" :value="$supplier->name" required autofocus />
                <x-forms.input name="contact_name" :label="__('Contact person')" :value="$supplier->contact_name" />
                <x-forms.input name="email" type="email" :label="__('Email')" :value="$supplier->email" />
                <x-forms.input name="phone" :label="__('Phone')" :value="$supplier->phone" />
                <x-forms.input name="website" type="url" :label="__('Website')" :value="$supplier->website" placeholder="https://" />
                <x-forms.input name="tax_id" :label="__('Tax ID')" :value="$supplier->tax_id" />
                <x-forms.input name="address" :label="__('Address')" :value="$supplier->address" />
                <x-forms.input name="country" :label="__('Country')" :value="$supplier->country" />
                <x-forms.textarea name="notes" :label="__('Notes')" :value="$supplier->notes" class="sm:col-span-2" />
                <x-forms.checkbox name="is_active" :label="__('Active supplier')" :checked="$supplier->is_active" class="sm:col-span-2" />
            </div>
        </div>
        <x-custom-fields :model="$supplier" />
        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
