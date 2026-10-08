<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()
            ->visibleTo($request->user())
            ->with(['customer', 'currency', 'invoice'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $like)->orWhere('transaction_id', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('company', 'like', $like))
                    ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', $like)));
            })
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date('to')));

        $completed = (clone $query)->where('status', Payment::STATUS_COMPLETED)->get();

        return view('payments.index', [
            'payments' => $query->latest('paid_at')->latest('id')->paginate(20)->withQueryString(),
            'total' => $completed->sum(fn (Payment $p) => $p->baseAmount()),
            'count' => $completed->count(),
            'byMethod' => $completed->groupBy('method')->map(fn ($group) => $group->sum(fn (Payment $p) => $p->baseAmount()))->sortDesc(),
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice_id') ? Invoice::query()->with('customer', 'currency')->find($request->integer('invoice_id')) : null;

        $payment = new Payment([
            'invoice_id' => $invoice?->id,
            'customer_id' => $invoice?->customer_id ?? ($request->integer('customer_id') ?: null),
            'currency_id' => $invoice?->currency_id ?? base_currency()?->id,
            'amount' => $invoice?->balance(),
            'method' => 'bank_transfer',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => today(),
        ]);

        return view('payments.form', ['payment' => $payment] + $this->formData());
    }

    public function store(Request $request, EmailService $emails): RedirectResponse
    {
        $payment = Payment::query()->create($this->validated($request) + ['recorded_by' => $request->user()->id]);

        if ($request->boolean('send_receipt') && $payment->status === Payment::STATUS_COMPLETED) {
            $emails->sendPaymentReceipt($payment);
        }

        $target = $payment->invoice_id ? route('invoices.show', $payment->invoice_id) : route('payments.show', $payment);

        return redirect($target)->with('success', __('Payment :reference recorded.', ['reference' => $payment->reference]));
    }

    public function show(Payment $payment): View
    {
        $this->ensureVisible($payment);
        $payment->load(['customer', 'currency', 'invoice.currency', 'recorder']);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        return view('payments.form', ['payment' => $payment] + $this->formData());
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $payment->update($this->validated($request));

        return redirect()->route('payments.show', $payment)->with('success', __('Payment updated.'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('payments.index')->with('success', __('Payment deleted.'));
    }

    public function receipt(Payment $payment, EmailService $emails): RedirectResponse
    {
        $log = $emails->sendPaymentReceipt($payment);

        return match (true) {
            $log === null => back()->with('warning', __('The customer has no e-mail or the template is disabled.')),
            $log->status === 'failed' => back()->with('error', __('The e-mail could not be sent: :error', ['error' => $log->error])),
            default => back()->with('success', __('Receipt sent to :email.', ['email' => $log->to])),
        };
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'customer_id' => ['required_without:invoice_id', 'nullable', 'exists:customers,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'status' => ['required', Rule::in(Payment::STATUSES)],
            'paid_at' => ['required', 'date'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::query()->findOrFail($data['invoice_id']);
            $data['customer_id'] = $invoice->customer_id;
            $data['currency_id'] = $invoice->currency_id ?? $data['currency_id'];
        }

        return $data;
    }

    protected function formData(): array
    {
        return [
            'invoices' => Invoice::query()->with('customer', 'currency')
                ->whereIn('status', [...Invoice::OPEN_STATUSES, Invoice::STATUS_DRAFT])
                ->latest('issue_date')->get(),
            'customers' => Customer::query()->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->displayName()]),
            'currencies' => Currency::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()]),
        ];
    }
}
