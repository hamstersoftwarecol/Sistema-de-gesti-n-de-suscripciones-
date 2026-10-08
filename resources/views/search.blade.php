<x-app-layout :title="__('Search')">
    <x-page-header :title="__('Search')" :subtitle="$term ? __('Results for “:term”', ['term' => $term]) : null" />

    <form method="GET" class="mb-6 sm:hidden">
        <input type="search" name="q" value="{{ $term }}" class="input" placeholder="{{ __('Search customers, subscriptions, invoices...') }}" autofocus>
    </form>

    @if (mb_strlen($term) < 2)
        <div class="card"><x-empty :message="__('Type at least 2 characters to search.')" icon="search" /></div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            @foreach (['customers' => __('Customers'), 'subscriptions' => __('Subscriptions'), 'invoices' => __('Invoices')] as $key => $label)
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ $label }} <span class="text-sm font-normal text-gray-500">({{ $results[$key]->count() }})</span></h2></div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($results[$key] as $item)
                            <li class="px-4 py-3 sm:px-6">
                                @if ($key === 'customers')
                                    <a href="{{ route('customers.show', $item) }}" class="link">{{ $item->displayName() }}</a>
                                    <p class="text-xs text-gray-500">{{ $item->code }} · {{ $item->email }}</p>
                                @elseif ($key === 'subscriptions')
                                    <a href="{{ route('subscriptions.show', $item) }}" class="link">{{ $item->reference }}</a>
                                    <p class="text-xs text-gray-500">{{ $item->customer?->name }} · {{ $item->plan?->fullName() }}</p>
                                @else
                                    <a href="{{ route('invoices.show', $item) }}" class="link">{{ $item->number }}</a>
                                    <p class="text-xs text-gray-500">{{ $item->customer?->name }} · {{ $item->format($item->total) }}</p>
                                @endif
                            </li>
                        @empty
                            <li><x-empty /></li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
