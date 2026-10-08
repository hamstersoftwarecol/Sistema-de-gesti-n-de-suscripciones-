<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Invoice::query()
            ->visibleTo($user)
            ->with(['customer', 'currency', 'subscription'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('number', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('company', 'like', $like)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date('to')));

        $totals = (clone $query)->get(['total', 'amount_paid', 'exchange_rate', 'status']);

        return view('invoices.index', [
            'invoices' => $query->latest('issue_date')->latest('id')->paginate(15)->withQueryString(),
            'summary' => [
                'total' => $totals->where('status', '!=', Invoice::STATUS_CANCELLED)->sum(fn ($i) => (float) $i->total / ((float) $i->exchange_rate ?: 1)),
                'paid' => $totals->sum(fn ($i) => (float) $i->amount_paid / ((float) $i->exchange_rate ?: 1)),
                'outstanding' => $totals->whereIn('status', Invoice::OPEN_STATUSES)->sum(fn ($i) => $i->balance() / ((float) $i->exchange_rate ?: 1)),
                'overdue' => $totals->where('status', Invoice::STATUS_OVERDUE)->count(),
            ],
            'customer' => $request->filled('customer_id') ? Customer::query()->find($request->integer('customer_id')) : null,
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer_id') ? Customer::query()->find($request->integer('customer_id')) : null;

        $invoice = new Invoice([
            'customer_id' => $customer?->id,
            'currency_id' => $customer?->currency_id ?? base_currency()?->id,
            'issue_date' => today(),
            'due_date' => today()->addDays((int) Setting::get('invoice_due_days', 7)),
            'tax_rate' => (float) Setting::get('default_tax_rate', 0),
            'status' => Invoice::STATUS_DRAFT,
            'notes' => Setting::get('invoice_notes'),
            'terms' => Setting::get('invoice_terms'),
        ]);

        return view('invoices.form', ['invoice' => $invoice, 'items' => []] + $this->formData());
    }

    public function store(Request $request, EmailService $emails): RedirectResponse
    {
        $data = $this->validated($request);

        $invoice = DB::transaction(function () use ($data) {
            $currency = Currency::query()->find($data['currency_id']);
            $invoice = Invoice::query()->create(collect($data)->except(['items', 'custom_fields', 'send_email'])->all() + [
                'exchange_rate' => $currency?->exchange_rate ?? 1,
            ]);
            $this->syncItems($invoice, $data['items']);

            return $invoice->recalculate();
        });

        $invoice->saveCustomFields($request->input('custom_fields'));

        if ($request->boolean('send_email') && $invoice->status !== Invoice::STATUS_DRAFT) {
            $emails->sendInvoice($invoice);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice :number created.', ['number' => $invoice->number]));
    }

    public function show(Invoice $invoice): View
    {
        $this->ensureVisible($invoice);

        $invoice->load(['customer', 'currency', 'items', 'payments.currency', 'subscription.plan.product']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load('items');

        $items = $invoice->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'discount' => (float) $item->discount,
            'product_id' => $item->product_id,
            'plan_id' => $item->plan_id,
        ])->all();

        return view('invoices.form', ['invoice' => $invoice, 'items' => $items] + $this->formData());
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $this->validated($request, $invoice);

        DB::transaction(function () use ($invoice, $data) {
            $currency = Currency::query()->find($data['currency_id']);
            $invoice->update(collect($data)->except(['items', 'custom_fields', 'send_email'])->all() + [
                'exchange_rate' => $currency?->exchange_rate ?? $invoice->exchange_rate,
            ]);
            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items']);
            $invoice->recalculate();
        });

        $invoice->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('invoices.show', $invoice)->with('success', __('Invoice updated.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', __('Invoice deleted.'));
    }

    public function pdf(Invoice $invoice, EmailService $emails): Response
    {
        $this->ensureVisible($invoice);

        return response($emails->invoicePdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function print(Invoice $invoice): View
    {
        $this->ensureVisible($invoice);
        $invoice->load(['customer', 'currency', 'items', 'payments', 'subscription.plan.product']);

        return view('invoices.pdf', ['invoice' => $invoice, 'printable' => true]);
    }

    public function send(Invoice $invoice, EmailService $emails): RedirectResponse
    {
        if ($invoice->status === Invoice::STATUS_DRAFT) {
            $invoice->update(['status' => Invoice::STATUS_SENT]);
        }

        $log = $emails->sendInvoice($invoice);

        return match (true) {
            $log === null => back()->with('warning', __('The customer has no e-mail or the template is disabled.')),
            $log->status === 'failed' => back()->with('error', __('The e-mail could not be sent: :error', ['error' => $log->error])),
            default => back()->with('success', __('Invoice sent to :email.', ['email' => $log->to])),
        };
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $copy = DB::transaction(function () use ($invoice) {
            $copy = $invoice->replicate(['number', 'amount_paid', 'sent_at', 'paid_at', 'status']);
            $copy->fill([
                'status' => Invoice::STATUS_DRAFT,
                'issue_date' => today(),
                'due_date' => today()->addDays((int) Setting::get('invoice_due_days', 7)),
                'amount_paid' => 0,
            ]);
            $copy->number = null;
            $copy->save();

            foreach ($invoice->items as $item) {
                $copy->items()->create($item->only(['product_id', 'plan_id', 'description', 'quantity', 'unit_price', 'discount', 'sort_order']));
            }

            return $copy->recalculate();
        });

        return redirect()->route('invoices.edit', $copy)->with('success', __('Invoice duplicated as draft :number.', ['number' => $copy->number]));
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

        return back()->with('success', __('Invoice cancelled.'));
    }

    protected function validated(Request $request, ?Invoice $invoice = null): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', Rule::in([Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_CANCELLED])],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'send_email' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.plan_id' => ['nullable', 'exists:plans,id'],
        ] + Invoice::customFieldRules());

        // Keep paid/partial/overdue states managed by payments when editing.
        if ($invoice && in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE], true) && $data['status'] === Invoice::STATUS_SENT) {
            $data['status'] = $invoice->status;
        }

        return $data;
    }

    protected function syncItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'product_id' => $item['product_id'] ?: null,
                'plan_id' => $item['plan_id'] ?: null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function formData(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->displayName().' — '.$c->code]),
            'currencies' => Currency::query()->active()->orderBy('code')->get(),
            'plans' => Plan::query()->with('product', 'currency')->active()->get()->sortBy(fn ($p) => $p->product?->name),
            'subscriptions' => Subscription::query()->with('customer')->live()->get()
                ->mapWithKeys(fn ($s) => [$s->id => $s->reference.' — '.$s->customer?->name]),
        ];
    }
}
