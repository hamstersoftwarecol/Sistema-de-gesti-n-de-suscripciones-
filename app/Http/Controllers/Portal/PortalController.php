<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Self-service area for customers: subscriptions, invoices and payments.
 */
class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $this->customer($request);

        $subscriptions = $customer->subscriptions()->with('plan.product', 'currency')->live()->orderBy('next_billing_date')->get();
        $openInvoices = $customer->invoices()->with('currency')->open()->orderBy('due_date')->get();

        return view('portal.dashboard', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'openInvoices' => $openInvoices,
            'recentPayments' => $customer->payments()->with('currency', 'invoice')->latest('paid_at')->take(5)->get(),
            'balance' => $openInvoices->sum(fn (Invoice $i) => $i->balance()),
        ]);
    }

    public function subscriptions(Request $request): View
    {
        return view('portal.subscriptions', [
            'subscriptions' => $this->customer($request)->subscriptions()->with('plan.product', 'currency')->latest()->get(),
        ]);
    }

    public function subscription(Request $request, Subscription $subscription): View
    {
        $this->own($request, $subscription->customer_id);

        return view('portal.subscription', [
            'subscription' => $subscription->load(['plan.product', 'currency', 'invoices' => fn ($q) => $q->with('currency')->where('status', '!=', Invoice::STATUS_DRAFT)->latest('issue_date')]),
        ]);
    }

    public function toggleAutoRenew(Request $request, Subscription $subscription, BillingService $billing): RedirectResponse
    {
        $this->own($request, $subscription->customer_id);
        abort_unless($subscription->isLive(), 403);

        if ($subscription->auto_renew) {
            $billing->cancel($subscription, false, __('Auto-renew disabled by the customer'));
            $message = __('Automatic renewal disabled. Your subscription stays active until :date.', ['date' => fdate($subscription->current_period_end)]);
        } else {
            $billing->resume($subscription);
            $message = __('Automatic renewal enabled.');
        }

        return back()->with('success', $message);
    }

    /** Cancellation request: ends the subscription at the end of the paid period and notifies the team. */
    public function cancel(Request $request, Subscription $subscription, BillingService $billing): RedirectResponse
    {
        $this->own($request, $subscription->customer_id);
        abort_unless($subscription->isLive(), 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $reason = $data['reason'] ?? __('Cancelled from the customer portal');

        $billing->cancel($subscription, false, $reason);

        // Create a follow-up task for the team on the Kanban board.
        if ($column = KanbanColumn::query()->orderBy('sort_order')->first()) {
            KanbanCard::query()->create([
                'kanban_column_id' => $column->id,
                'title' => __('Cancellation request: :customer', ['customer' => $subscription->customer?->name]),
                'description' => $subscription->reference.' — '.$reason,
                'priority' => 'high',
                'due_date' => today()->addDays(2),
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'sort_order' => 0,
            ]);
        }

        return back()->with('success', __('Your subscription will end on :date. We are sorry to see you go!', ['date' => fdate($subscription->current_period_end)]));
    }

    public function invoices(Request $request): View
    {
        return view('portal.invoices', [
            'invoices' => $this->customer($request)->invoices()->with('currency')
                ->where('status', '!=', Invoice::STATUS_DRAFT)->latest('issue_date')->paginate(15),
        ]);
    }

    public function invoice(Request $request, Invoice $invoice): View
    {
        $this->own($request, $invoice->customer_id);
        abort_if($invoice->status === Invoice::STATUS_DRAFT, 404);

        return view('portal.invoice', ['invoice' => $invoice->load('items', 'currency', 'payments.currency', 'subscription')]);
    }

    public function invoicePdf(Request $request, Invoice $invoice, EmailService $emails): Response
    {
        $this->own($request, $invoice->customer_id);
        abort_if($invoice->status === Invoice::STATUS_DRAFT, 404);

        return response($emails->invoicePdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function payments(Request $request): View
    {
        return view('portal.payments', [
            'payments' => $this->customer($request)->payments()->with('currency', 'invoice')
                ->where('status', '!=', Payment::STATUS_FAILED)->latest('paid_at')->paginate(15),
        ]);
    }

    protected function customer(Request $request): Customer
    {
        $customer = $request->user()->customer;

        abort_unless($customer, 403, __('Your user is not linked to a customer account.'));

        return $customer;
    }

    protected function own(Request $request, ?int $customerId): void
    {
        abort_unless($customerId && $customerId === $request->user()->customer_id, 404);
    }
}
