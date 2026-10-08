<x-app-layout :title="$customer->name">
    @php $isStaff = auth()->user()->isStaff(); @endphp

    <x-page-header :title="$customer->displayName()" :subtitle="$customer->code.' · '.__('Customer since :date', ['date' => fdate($customer->created_at)])" :back="route('customers.index')">
        <x-slot:actions>
            <a href="{{ route('subscriptions.create', ['customer_id' => $customer->id]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('Subscription') }}</a>
            @if ($isStaff)
                <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> {{ __('Invoice') }}</a>
            @endif
            <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> {{ __('Edit') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="\App\Models\Customer::statusOptions()[$customer->status] ?? $customer->status" icon="user" />
        <x-stat :label="__('MRR')" :value="money($mrr)" icon="refresh" color="green" />
        <x-stat :label="__('Outstanding')" :value="money($balance, $customer->currency)" icon="clock" :color="$balance > 0 ? 'amber' : 'green'" />
        <x-stat :label="__('Subscriptions')" :value="$customer->subscriptions->count()" icon="cube" color="blue" />
    </div>

    @if (session('ai_analysis'))
        <div class="card mt-6 ring-2 ring-primary-500/40">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5 text-primary-500" /> {{ __('Gemini AI analysis') }}</h2>
            </div>
            <div class="card-body prose-ai text-sm text-gray-700 dark:text-gray-300">
                {!! \Illuminate\Support\Str::markdown(session('ai_analysis'), ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Details') }}</h2></div>
                <dl class="card-body divide-y divide-gray-100 py-2 dark:divide-gray-800">
                    <div class="dl-row"><dt>{{ __('Email') }}</dt><dd class="truncate">{{ $customer->email ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Phone') }}</dt><dd>{{ $customer->phone ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Tax ID') }}</dt><dd>{{ $customer->tax_id ?? '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Address') }}</dt><dd>{{ collect([$customer->address, $customer->city, $customer->state, $customer->postal_code, $customer->country])->filter()->implode(', ') ?: '—' }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Currency') }}</dt><dd>{{ $customer->currency?->code ?? base_currency()?->code }}</dd></div>
                    <div class="dl-row"><dt>{{ __('Seller') }}</dt><dd>{{ $customer->seller?->name ?? '—' }}</dd></div>
                </dl>
                <x-custom-fields-display :model="$customer" class="border-t border-gray-100 px-4 py-2 dark:border-gray-800 sm:px-6" />
                @if ($customer->notes)
                    <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400 sm:px-6">{!! nl2br(e($customer->notes)) !!}</div>
                @endif
            </div>

            @if ($isStaff)
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5 text-primary-500" /> {{ __('AI analysis') }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 text-sm text-gray-500">{{ __('Gemini evaluates churn risk, payment behaviour and upsell opportunities for this customer.') }}</p>
                        <form method="POST" action="{{ route('customers.ai-analysis', $customer) }}" x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            <button class="btn-primary w-full" :disabled="loading">
                                <x-icon name="sparkles" class="h-4 w-4" />
                                <span x-show="!loading">{{ __('Analyze with Gemini') }}</span>
                                <span x-show="loading" x-cloak>{{ __('Analyzing...') }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ __('Customer portal') }}</h2>
                        @if ($customer->user)
                            <x-status :value="(bool) $customer->user->is_active" type="bool" />
                        @endif
                    </div>
                    <form method="POST" action="{{ route('customers.portal-access', $customer) }}" class="card-body space-y-3">
                        @csrf
                        @if ($customer->user?->last_login_at)
                            <p class="text-xs text-gray-500">{{ __('Last login: :date', ['date' => fdate($customer->user->last_login_at, true)]) }}</p>
                        @endif
                        <x-forms.input name="email" type="email" :label="__('Login e-mail')" :value="$customer->user?->email ?? $customer->email" required />
                        <x-forms.input name="password" type="text" :label="$customer->user ? __('New password (optional)') : __('Password (optional)')" :hint="__('Leave empty to generate one automatically.')" />
                        <x-forms.checkbox name="send_email" :label="__('Send the credentials by e-mail')" :checked="true" />
                        @if ($customer->user)
                            <x-forms.checkbox name="is_active" :label="__('Access enabled')" :checked="$customer->user->is_active" />
                        @endif
                        <button class="btn-secondary w-full"><x-icon name="key" class="h-4 w-4" /> {{ $customer->user ? __('Update access') : __('Enable portal access') }}</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Subscriptions') }}</h2></div>
                @if ($customer->subscriptions->isEmpty())
                    <x-empty :message="__('This customer has no subscriptions.')" icon="refresh" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Next billing') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @foreach ($customer->subscriptions as $subscription)
                                    <tr>
                                        <td><a href="{{ route('subscriptions.show', $subscription) }}" class="link">{{ $subscription->reference }}</a></td>
                                        <td>{{ $subscription->plan?->fullName() }}</td>
                                        <td>{{ $subscription->currency?->format($subscription->periodAmount()) }} <span class="text-xs text-gray-500">/ {{ mb_strtolower($subscription->plan?->cycleLabel() ?? '') }}</span></td>
                                        <td>{{ fdate($subscription->next_billing_date) }}</td>
                                        <td><x-status :value="$subscription->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Latest invoices') }}</h2>
                    <a href="{{ route('invoices.index', ['customer_id' => $customer->id]) }}" class="link text-sm">{{ __('View all') }}</a>
                </div>
                @if ($customer->invoices->isEmpty())
                    <x-empty icon="document" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due date') }}</th><th class="text-right">{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @foreach ($customer->invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                                        <td>{{ fdate($invoice->issue_date) }}</td>
                                        <td>{{ fdate($invoice->due_date) }}</td>
                                        <td class="text-right">{{ $invoice->format($invoice->total) }}</td>
                                        <td><x-status :value="$invoice->status" type="invoice" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Latest payments') }}</h2></div>
                @if ($customer->payments->isEmpty())
                    <x-empty icon="banknotes" />
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                            <tbody>
                                @foreach ($customer->payments as $payment)
                                    <tr>
                                        <td><a href="{{ route('payments.show', $payment) }}" class="link">{{ $payment->reference }}</a></td>
                                        <td>{{ fdate($payment->paid_at) }}</td>
                                        <td>{{ \App\Models\Payment::methodOptions()[$payment->method] ?? $payment->method }}</td>
                                        <td class="text-right">{{ $payment->format() }}</td>
                                        <td><x-status :value="$payment->status" type="payment" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
