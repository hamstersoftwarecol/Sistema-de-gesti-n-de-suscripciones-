<x-app-layout :title="$product->exists ? __('Edit product') : __('New product')">
    <x-page-header :title="$product->exists ? __('Edit product') : __('New product')" :back="$product->exists ? route('products.show', $product) : route('products.index')" />

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="space-y-6">
        @csrf
        @if ($product->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-forms.input name="name" :label="__('Name')" :value="$product->name" required autofocus />
                <x-forms.input name="sku" :label="__('SKU / code')" :value="$product->sku" />
                <x-forms.select name="type" :label="__('Type')" :options="\App\Models\Product::typeOptions()" :value="$product->type" required />
                <x-forms.select name="product_category_id" :label="__('Category')" :options="$categories" :value="$product->product_category_id" :placeholder="__('No category')" />
                <x-forms.select name="supplier_id" :label="__('Supplier')" :options="$suppliers" :value="$product->supplier_id" :placeholder="__('No supplier')" />
                <x-forms.input name="cost" type="number" step="0.01" min="0" :label="__('Unit cost (per period)')" :value="$product->cost" :hint="__('What the supplier charges you, used to estimate margins.')" />
                <x-forms.textarea name="description" :label="__('Description')" :value="$product->description" class="sm:col-span-2" rows="4" />
                <x-forms.checkbox name="is_active" :label="__('Active (available for new subscriptions)')" :checked="$product->is_active" class="sm:col-span-2" />
            </div>
        </div>
        <x-custom-fields :model="$product" />
        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
