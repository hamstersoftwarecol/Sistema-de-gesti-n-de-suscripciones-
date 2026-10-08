<x-app-layout :title="__('Customers')">
    <x-page-header :title="__('Customers')" :subtitle="__(':count records', ['count' => $customers->total()])">
        <x-slot:actions>
            <a href="{{ route('customers.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New customer') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <div class="min-w-[12rem] flex-1">
                <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Name, company, e-mail, code or tax ID') }}">
            </div>
            <select name="status" class="input w-auto">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Models\Customer::statusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if (auth()->user()->isStaff())
                <select name="seller_id" class="input w-auto">
                    <option value="">{{ __('All sellers') }}</option>
                    @foreach ($sellers as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('seller_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            @endif
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>

        @if ($customers->isEmpty())
            <x-empty :message="__('No customers yet.')" icon="users">
                <a href="{{ route('customers.create') }}" class="btn-primary">{{ __('New customer') }}</a>
            </x-empty>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th>{{ __('Seller') }}</th>
                            @foreach ($tableFields as $field)
                                <th>{{ $field->label }}</th>
                            @endforeach
                            <th>{{ __('Subscriptions') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="link">{{ $customer->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $customer->code }}{{ $customer->company ? ' · '.$customer->company : '' }}</p>
                                </td>
                                <td>
                                    <p>{{ $customer->email ?? '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $customer->phone }}</p>
                                </td>
                                <td>{{ $customer->seller?->name ?? '—' }}</td>
                                @foreach ($tableFields as $field)
                                    <td>{{ $field->displayValue($customer->customFieldValue($field)) }}</td>
                                @endforeach
                                <td>{{ $customer->live_subscriptions_count }}</td>
                                <td><x-status :value="$customer->status" type="customer" /></td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn-icon h-8 w-8" title="{{ __('Edit') }}"><x-icon name="pencil" class="h-4 w-4" /></a>
                                        @if (auth()->user()->isStaff())
                                            <x-delete-button :action="route('customers.destroy', $customer)" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $customers->links() }}</div>
        @endif
    </div>
</x-app-layout>
