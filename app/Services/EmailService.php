<?php

namespace App\Services;

use App\Mail\TemplateMail;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\RuntimeConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the editable e-mail templates and keeps a log of every delivery.
 */
class EmailService
{
    public function send(string $templateKey, ?string $to, array $variables, array $context = [], array $attachments = []): ?EmailLog
    {
        $template = EmailTemplate::findActive($templateKey);

        if (! $template || blank($to)) {
            return null;
        }

        $variables = array_merge($this->globalVariables(), $variables);
        $rendered = $template->render($variables);

        $log = new EmailLog(array_merge($context, [
            'template_key' => $templateKey,
            'to' => $to,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ]));

        try {
            RuntimeConfig::applyMail();
            Mail::to($to)->send(new TemplateMail($rendered['subject'], $rendered['body'], $attachments));
            $log->status = 'sent';
            $log->sent_at = now();
        } catch (Throwable $e) {
            report($e);
            $log->status = 'failed';
            $log->error = mb_substr($e->getMessage(), 0, 1000);
        }

        $log->save();

        return $log;
    }

    public function sendForSubscription(string $templateKey, Subscription $subscription, array $extra = []): ?EmailLog
    {
        $subscription->loadMissing('customer', 'plan.product', 'currency');

        return $this->send(
            $templateKey,
            $subscription->customer?->email,
            array_merge($this->subscriptionVariables($subscription), $extra),
            ['customer_id' => $subscription->customer_id, 'subscription_id' => $subscription->id],
        );
    }

    public function sendInvoice(Invoice $invoice, string $templateKey = EmailTemplate::INVOICE_CREATED): ?EmailLog
    {
        $invoice->loadMissing('customer', 'currency', 'items', 'subscription.plan.product');

        $attachments = [];
        try {
            $attachments[] = [
                'data' => $this->invoicePdf($invoice),
                'name' => $invoice->number.'.pdf',
                'mime' => 'application/pdf',
            ];
        } catch (Throwable $e) {
            report($e);
        }

        $log = $this->send(
            $templateKey,
            $invoice->customer?->email,
            $this->invoiceVariables($invoice),
            ['customer_id' => $invoice->customer_id, 'subscription_id' => $invoice->subscription_id, 'invoice_id' => $invoice->id],
            $attachments,
        );

        if ($log?->status === 'sent' && $templateKey === EmailTemplate::INVOICE_CREATED) {
            $invoice->forceFill([
                'sent_at' => now(),
                'status' => $invoice->status === Invoice::STATUS_DRAFT ? Invoice::STATUS_SENT : $invoice->status,
            ])->save();
        }

        return $log;
    }

    public function sendPaymentReceipt(Payment $payment): ?EmailLog
    {
        $payment->loadMissing('customer', 'currency', 'invoice');

        return $this->send(
            EmailTemplate::PAYMENT_RECEIVED,
            $payment->customer?->email,
            [
                'customer_name' => $payment->customer?->name,
                'payment_amount' => $payment->format(),
                'invoice_number' => $payment->invoice?->number ?? '—',
                'invoice_balance' => $payment->invoice ? $payment->invoice->format($payment->invoice->balance()) : '—',
            ],
            ['customer_id' => $payment->customer_id, 'invoice_id' => $payment->invoice_id],
        );
    }

    public function sendPortalWelcome(Customer $customer, string $email, string $password): ?EmailLog
    {
        return $this->send(
            EmailTemplate::PORTAL_WELCOME,
            $email,
            [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'login_email' => $email,
                'login_password' => $password,
            ],
            ['customer_id' => $customer->id],
        );
    }

    public function invoicePdf(Invoice $invoice): string
    {
        $invoice->loadMissing('customer', 'currency', 'items', 'payments', 'subscription.plan.product');

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4')->output();
    }

    public function subscriptionVariables(Subscription $subscription): array
    {
        $currency = $subscription->currency;

        return [
            'customer_name' => $subscription->customer?->name,
            'customer_email' => $subscription->customer?->email,
            'subscription_reference' => $subscription->reference,
            'plan_name' => $subscription->plan?->fullName(),
            'amount' => $currency ? $currency->format($subscription->periodAmount()) : number_format($subscription->periodAmount(), 2),
            'renewal_date' => fdate($subscription->next_billing_date ?? $subscription->ends_at),
            'days_left' => $subscription->daysUntilRenewal() ?? 0,
        ];
    }

    public function invoiceVariables(Invoice $invoice): array
    {
        return [
            'customer_name' => $invoice->customer?->name,
            'customer_email' => $invoice->customer?->email,
            'invoice_number' => $invoice->number,
            'invoice_total' => $invoice->format($invoice->total),
            'invoice_balance' => $invoice->format($invoice->balance()),
            'invoice_due_date' => fdate($invoice->due_date),
            'subscription_reference' => $invoice->subscription?->reference ?? '—',
            'plan_name' => $invoice->subscription?->plan?->fullName() ?? '—',
        ];
    }

    protected function globalVariables(): array
    {
        return [
            'company_name' => setting('company_name', config('app.name')),
            'portal_url' => url('/portal'),
        ];
    }
}
