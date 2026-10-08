<x-app-layout :title="__('Suppliers')">
    <x-page-header :title="__('Suppliers')" :subtitle="__('Vendors and providers of your catalog')">
        <x-slot:actions>
            <a href="{{ route('suppliers.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New supplier') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Name, contact or e-mail') }}">
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4" /></button>
        </form>
        @if ($suppliers->isEmpty())
            <x-empty :message="__('No suppliers yet.')" icon="truck" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Supplier') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Country') }}</th><th>{{ __('Products') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td><a href="{{ route('suppliers.show', $supplier) }}" class="link">{{ $supplier->name }}</a>
                                    @if ($supplier->website) <p class="text-xs text-gray-500">{{ parse_url($supplier->website, PHP_URL_HOST) ?? $supplier->website }}</p> @endif</td>
                                <td>{{ $supplier->contact_name ?? '—' }}<p class="text-xs text-gray-500">{{ $supplier->email }} {{ $supplier->phone }}</p></td>
                                <td>{{ $supplier->country ?? '—' }}</td>
                                <td>{{ $supplier->products_count }}</td>
                                <td><x-status :value="(bool) $supplier->is_active" type="bool" /></td>
                                <td class="text-right">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-icon h-8 w-8"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    <x-delete-button :action="route('suppliers.destroy', $supplier)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $suppliers->links() }}</div>
        @endif
    </div>
</x-app-layout>
