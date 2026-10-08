<x-app-layout :title="$customer->exists ? __('Edit customer') : __('New customer')">
    <x-page-header :title="$customer->exists ? __('Edit customer') : __('New customer')"
                   :subtitle="$customer->exists ? $customer->code : null"
                   :back="$customer->exists ? route('customers.show', $customer) : route('customers.index')" />

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="space-y-6">
        @csrf
        @if ($customer->exists) @method('PUT') @endif

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('General information') }}</h2></div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-forms.input name="name" :label="__('Full name')" :value="$customer->name" required autofocus />
                <x-forms.input name="company" :label="__('Company')" :value="$customer->company" />
                <x-forms.input name="email" type="email" :label="__('Email')" :value="$customer->email" />
                <x-forms.input name="phone" :label="__('Phone')" :value="$customer->phone" />
                <x-forms.input name="tax_id" :label="__('Tax ID')" :value="$customer->tax_id" />
                <x-forms.select name="status" :label="__('Status')" :options="\App\Models\Customer::statusOptions()" :value="$customer->status" required />
                <x-forms.select name="currency_id" :label="__('Currency')" :options="$currencies" :value="$customer->currency_id" :placeholder="__('Base currency')" />
                @if (auth()->user()->isStaff())
                    <x-forms.select name="seller_id" :label="__('Seller')" :options="$sellers" :value="$customer->seller_id" :placeholder="__('No seller')" />
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Address') }}</h2></div>
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-forms.input name="address" :label="__('Address')" :value="$customer->address" class="sm:col-span-2 lg:col-span-3" />
                <x-forms.input name="city" :label="__('City')" :value="$customer->city" />
                <x-forms.input name="state" :label="__('State / province')" :value="$customer->state" />
                <x-forms.input name="postal_code" :label="__('Postal code')" :value="$customer->postal_code" />
                <x-forms.input name="country" :label="__('Country')" :value="$customer->country" />
            </div>
        </div>

        <x-custom-fields :model="$customer" />

        <div class="card">
            <div class="card-body">
                <x-forms.textarea name="notes" :label="__('Internal notes')" :value="$customer->notes" rows="4" />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
