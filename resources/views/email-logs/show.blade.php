<x-app-layout :title="$log->subject">
    <x-page-header :title="$log->subject" :subtitle="$log->to.' · '.fdate($log->created_at, true)" :back="route('email-logs.index')" />
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card">
            <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                <div class="dl-row"><dt>{{ __('Status') }}</dt><dd><x-status :value="$log->status" type="email" /></dd></div>
                <div class="dl-row"><dt>{{ __('Template') }}</dt><dd class="font-mono text-xs">{{ $log->template_key }}</dd></div>
                <div class="dl-row"><dt>{{ __('Customer') }}</dt><dd>@if ($log->customer)<a href="{{ route('customers.show', $log->customer) }}" class="link">{{ $log->customer->name }}</a>@else — @endif</dd></div>
                <div class="dl-row"><dt>{{ __('Subscription') }}</dt><dd>@if ($log->subscription)<a href="{{ route('subscriptions.show', $log->subscription) }}" class="link">{{ $log->subscription->reference }}</a>@else — @endif</dd></div>
                <div class="dl-row"><dt>{{ __('Invoice') }}</dt><dd>@if ($log->invoice)<a href="{{ route('invoices.show', $log->invoice) }}" class="link">{{ $log->invoice->number }}</a>@else — @endif</dd></div>
            </dl>
            @if ($log->error)
                <div class="border-t border-gray-200 p-4 text-xs text-red-600 dark:border-gray-800">{{ $log->error }}</div>
            @endif
        </div>
        <div class="card card-body whitespace-pre-line text-sm lg:col-span-2">{{ $log->body }}</div>
    </div>
</x-app-layout>
