<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomField;
use App\Models\EmailTemplate;
use App\Models\Plan;
use App\Models\Seller;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $subscriptions = Subscription::query()
            ->visibleTo($user)
            ->with(['customer', 'plan.product', 'currency', 'seller'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('company', 'like', $like)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('plan_id'), fn ($q) => $q->where('plan_id', $request->integer('plan_id')))
            ->when($request->filled('seller_id') && $user->isStaff(), fn ($q) => $q->where('seller_id', $request->integer('seller_id')))
            ->when($request->input('renewal') === 'week', fn ($q) => $q->live()->whereBetween('next_billing_date', [today()->toDateString(), today()->addDays(7)->toDateString()]))
            ->when($request->input('renewal') === 'month', fn ($q) => $q->live()->whereBetween('next_billing_date', [today()->toDateString(), today()->addDays(30)->toDateString()]))
            ->orderByRaw("case status when 'past_due' then 0 when 'trial' then 1 when 'active' then 2 when 'paused' then 3 else 4 end")
            ->orderBy('next_billing_date')
            ->paginate(15)
            ->withQueryString();

        $counts = Subscription::query()->visibleTo($user)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('subscriptions.index', [
            'subscriptions' => $subscriptions,
            'counts' => $counts,
            'plans' => Plan::query()->with('product')->get()->mapWithKeys(fn ($p) => [$p->id => $p->fullName()]),
            'sellers' => Seller::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $subscription = new Subscription([
            'customer_id' => $request->integer('customer_id') ?: null,
            'start_date' => today(),
            'quantity' => 1,
            'discount' => 0,
            'auto_renew' => true,
        ]);

        return view('subscriptions.create', ['subscription' => $subscription] + $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->isSeller()) {
            $request->merge(['seller_id' => $request->user()->seller?->id]);
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'seller_id' => ['nullable', 'exists:sellers,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_renew' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'issue_invoice' => ['boolean'],
        ] + Subscription::customFieldRules());

        $customer = Customer::query()->findOrFail($data['customer_id']);
        $this->ensureVisible($customer);

        $plan = Plan::query()->findOrFail($data['plan_id']);

        $subscription = $this->billing->subscribe([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'seller_id' => $data['seller_id'] ?? $customer->seller_id,
            'currency_id' => $plan->currency_id ?? $customer->currency_id ?? base_currency()?->id,
            'price' => $data['price'] ?? $plan->price,
            'quantity' => $data['quantity'],
            'discount' => $data['discount'] ?? 0,
            'start_date' => $data['start_date'],
            'trial_days' => $data['trial_days'] ?? $plan->trial_days,
            'auto_renew' => $request->boolean('auto_renew'),
            'notes' => $data['notes'] ?? null,
        ], $request->boolean('issue_invoice', true));

        $subscription->saveCustomFields($request->input('custom_fields'));

        if ($customer->status === 'lead') {
            $customer->update(['status' => 'active']);
        }

        return redirect()->route('subscriptions.show', $subscription)->with('success', __('Subscription created successfully.'));
    }

    public function show(Subscription $subscription): View
    {
        $this->ensureVisible($subscription);

        $subscription->load([
            'customer', 'plan.product', 'currency', 'seller',
            'invoices' => fn ($q) => $q->with('currency')->latest('issue_date'),
            'renewals.invoice',
        ]);

        return view('subscriptions.show', compact('subscription'));
    }

    public function edit(Subscription $subscription, Request $request): View
    {
        $this->ensureVisible($subscription);

        return view('subscriptions.edit', ['subscription' => $subscription] + $this->formData($request));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ensureVisible($subscription);

        if ($request->user()->isSeller()) {
            $request->merge(['seller_id' => $subscription->seller_id]);
        }

        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'seller_id' => ['nullable', 'exists:sellers,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'next_billing_date' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date'],
            'auto_renew' => ['boolean'],
            'status' => ['required', Rule::in(Subscription::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ] + Subscription::customFieldRules());

        $subscription->update(collect($data)->except('custom_fields')->all() + ['discount' => $data['discount'] ?? 0]);
        $subscription->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('subscriptions.show', $subscription)->with('success', __('Subscription updated successfully.'));
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', __('Subscription deleted.'));
    }

    public function renew(Subscription $subscription): RedirectResponse
    {
        if (in_array($subscription->status, [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED], true)) {
            $subscription->forceFill([
                'status' => Subscription::STATUS_ACTIVE,
                'cancelled_at' => null,
                'ends_at' => null,
                'next_billing_date' => today(),
            ])->save();
        }

        $invoice = $this->billing->renew($subscription, automatic: false);

        return redirect()->route('invoices.show', $invoice)->with('success', __('Subscription renewed. Invoice :number issued.', ['number' => $invoice->number]));
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ensureVisible($subscription);

        $data = $request->validate([
            'when' => ['required', 'in:now,period_end'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->billing->cancel($subscription, $data['when'] === 'now', $data['reason'] ?? null);

        return back()->with('success', $data['when'] === 'now'
            ? __('Subscription cancelled.')
            : __('The subscription will end on :date.', ['date' => fdate($subscription->current_period_end)]));
    }

    public function pause(Subscription $subscription): RedirectResponse
    {
        $this->ensureVisible($subscription);
        $this->billing->pause($subscription);

        return back()->with('success', __('Subscription paused. It will not be billed until resumed.'));
    }

    public function resume(Subscription $subscription): RedirectResponse
    {
        $this->ensureVisible($subscription);
        $this->billing->resume($subscription);

        return back()->with('success', __('Subscription resumed.'));
    }

    public function remind(Subscription $subscription, EmailService $emails): RedirectResponse
    {
        $this->ensureVisible($subscription);

        $log = $emails->sendForSubscription(EmailTemplate::RENEWAL_REMINDER, $subscription);

        return match (true) {
            $log === null => back()->with('warning', __('The customer has no e-mail or the template is disabled.')),
            $log->status === 'failed' => back()->with('error', __('The e-mail could not be sent: :error', ['error' => $log->error])),
            default => back()->with('success', __('Reminder sent to :email.', ['email' => $log->to])),
        };
    }

    protected function formData(Request $request): array
    {
        $plans = Plan::query()->with('product', 'currency')->active()
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($p) => $p->product->name.$p->price);

        return [
            'customers' => Customer::query()->visibleTo($request->user())->orderBy('name')->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->displayName().' — '.$c->code]),
            'plans' => $plans,
            'sellers' => Seller::query()->active()->orderBy('name')->pluck('name', 'id'),
            'customFields' => CustomField::for('subscription'),
        ];
    }
}
