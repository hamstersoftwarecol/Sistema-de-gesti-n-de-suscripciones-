<x-app-layout :title="__('Categories')">
    <x-page-header :title="__('Product categories')" :back="route('products.index')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('categories.store') }}" class="card h-fit">
            @csrf
            <div class="card-header"><h2 class="card-title">{{ __('New category') }}</h2></div>
            <div class="card-body space-y-4">
                <x-forms.input name="name" :label="__('Name')" required />
                <x-forms.input name="description" :label="__('Description')" />
                <x-forms.input name="color" type="color" :label="__('Color')" value="#6366f1" class="w-24" />
                <button class="btn-primary w-full">{{ __('Create') }}</button>
            </div>
        </form>

        <div class="card lg:col-span-2">
            @if ($categories->isEmpty())
                <x-empty icon="tag" />
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($categories as $category)
                        <li class="p-4 sm:px-6" x-data="{ editing: false }">
                            <div class="flex items-center justify-between gap-3" x-show="!editing">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="h-4 w-4 shrink-0 rounded-full" style="background: {{ $category->color }}"></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ $category->name }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ $category->description }} · {{ trans_choice(':count product|:count products', $category->products_count, ['count' => $category->products_count]) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="btn-icon h-8 w-8" @click="editing = true"><x-icon name="pencil" class="h-4 w-4" /></button>
                                    <x-delete-button :action="route('categories.destroy', $category)" />
                                </div>
                            </div>
                            <form method="POST" action="{{ route('categories.update', $category) }}" x-show="editing" x-cloak class="flex flex-wrap items-end gap-3">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $category->name }}" class="input flex-1" required>
                                <input name="description" value="{{ $category->description }}" class="input flex-1" placeholder="{{ __('Description') }}">
                                <input type="color" name="color" value="{{ $category->color }}" class="h-9 w-14 rounded border-gray-300">
                                <button class="btn-primary btn-sm">{{ __('Save') }}</button>
                                <button type="button" class="btn-secondary btn-sm" @click="editing = false">{{ __('Cancel') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
