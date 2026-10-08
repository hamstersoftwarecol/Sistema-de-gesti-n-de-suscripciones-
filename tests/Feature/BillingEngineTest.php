<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsBillingData;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use BuildsBillingData, RefreshDatabase;

    protected BillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        Setting::set(['invoice_due_days' => 10, 'default_tax_rate' => 0, 'invoice_prefix' => 'INV-']);
        $this->billing = app(BillingService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_subscription_issues_first_invoice_and_schedules_next_period(): void
    {
        $plan = $this->makePlan(['setup_fee' => 25]);
        $customer = $this->makeCustomer();

        $subscription = $this->billing->subscribe([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'quantity' => 2,
            'discount' => 10,
            'start_date' => '2026-01-31',
        ]);

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame('2026-01-31', $subscription->current_period_start->toDateString());
        // Month overflow is avoided: Jan 31 + 1 month = Feb 28.
        $this->assertSame('2026-02-28', $subscription->next_billing_date->toDateString());
        $this->assertSame('2026-02-27', $subscription->current_period_end->toDateString());

        $invoice = $subscription->invoices()->with('items')->sole();
        $this->assertCount(2, $invoice->items);
        // 2 × 100 − 10% = 180 + 25 setup fee.
        $this->assertEquals(205.00, (float) $invoice->total);
        $this->assertEquals(20.00, (float) $invoice->discount_total);
        $this->assertSame('2026-02-10', $invoice->due_date->toDateString());
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->number);
        $this->assertSame(1, $subscription->renewals()->count());
    }

    public function test_trial_subscription_is_invoiced_when_the_trial_ends(): void
    {
        Carbon::setTestNow('2026-03-01');
        $plan = $this->makePlan(['trial_days' => 14]);

        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-03-01',
        ]);

        $this->assertSame(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertSame('2026-03-15', $subscription->next_billing_date->toDateString());
        $this->assertSame(0, $subscription->invoices()->count());

        $this->billing->run(Carbon::parse('2026-03-14'));
        $this->assertSame(0, $subscription->invoices()->count());

        $summary = $this->billing->run(Carbon::parse('2026-03-15'));

        $subscription->refresh();
        $this->assertSame(1, $summary['invoices']);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame('2026-04-15', $subscription->next_billing_date->toDateString());
        $this->assertSame(1, $subscription->invoices()->count());
    }

    public function test_engine_catches_up_every_missed_period(): void
    {
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-10',
        ]);

        $summary = $this->billing->run(Carbon::parse('2026-04-12'));

        // Feb 10, Mar 10 and Apr 10 periods + the initial January invoice.
        $this->assertSame(3, $summary['invoices']);
        $this->assertSame(4, $subscription->invoices()->count());
        $this->assertSame('2026-05-10', $subscription->fresh()->next_billing_date->toDateString());

        // Running twice on the same day is idempotent.
        $this->billing->run(Carbon::parse('2026-04-12'));
        $this->assertSame(4, $subscription->invoices()->count());
    }

    public function test_yearly_plan_with_interval_renews_on_the_right_date(): void
    {
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan(['billing_cycle' => 'quarterly', 'interval_count' => 2])->id,
            'start_date' => '2026-01-15',
        ]);

        $this->assertSame('2026-07-15', $subscription->next_billing_date->toDateString());
        $this->assertEqualsWithDelta(100 / 6, $subscription->monthlyAmount(), 0.001);
    }

    public function test_subscription_without_auto_renew_expires_at_period_end(): void
    {
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);

        $this->billing->cancel($subscription, immediately: false, reason: 'Too expensive');
        $subscription->refresh();
        $this->assertFalse($subscription->auto_renew);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame('2026-01-31', $subscription->ends_at->toDateString());

        $summary = $this->billing->run(Carbon::parse('2026-02-01'));

        $subscription->refresh();
        $this->assertSame(1, $summary['expired']);
        $this->assertSame(Subscription::STATUS_EXPIRED, $subscription->status);
        $this->assertNull($subscription->next_billing_date);
        $this->assertSame(1, $subscription->invoices()->count());
    }

    public function test_immediate_cancellation_stops_billing(): void
    {
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);

        $this->billing->cancel($subscription, immediately: true);
        $this->billing->run(Carbon::parse('2026-06-01'));

        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
        $this->assertSame(1, $subscription->invoices()->count());
    }

    public function test_paused_subscriptions_are_not_billed_until_resumed(): void
    {
        Carbon::setTestNow('2026-01-01');
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);

        $this->billing->pause($subscription);
        $this->billing->run(Carbon::parse('2026-03-01'));
        $this->assertSame(1, $subscription->invoices()->count());

        Carbon::setTestNow('2026-03-05');
        $this->billing->resume($subscription->fresh());
        $this->assertSame('2026-03-05', $subscription->fresh()->next_billing_date->toDateString());

        $this->billing->run(Carbon::parse('2026-03-05'));
        $this->assertSame(2, $subscription->invoices()->count());
    }

    public function test_overdue_invoice_marks_subscription_past_due_and_payment_restores_it(): void
    {
        $subscription = $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);
        $invoice = $subscription->invoices()->sole();

        $summary = $this->billing->run(Carbon::parse('2026-01-20'));

        $this->assertSame(1, $summary['overdue']);
        $this->assertSame(Invoice::STATUS_OVERDUE, $invoice->fresh()->status);
        $this->assertSame(Subscription::STATUS_PAST_DUE, $subscription->fresh()->status);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'currency_id' => $invoice->currency_id,
            'amount' => 40,
            'method' => 'cash',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => '2026-01-21',
        ]);

        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
        $this->assertEquals(60.0, $invoice->fresh()->balance());
        $this->assertSame(Subscription::STATUS_PAST_DUE, $subscription->fresh()->status);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'currency_id' => $invoice->currency_id,
            'amount' => 60,
            'method' => 'card',
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => '2026-01-22',
        ]);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame('2026-01-22', $invoice->paid_at->toDateString());
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);

        // Deleting a payment re-opens the invoice.
        $invoice->payments()->first()->delete();
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_invoice_totals_include_line_discounts_and_taxes(): void
    {
        $invoice = Invoice::query()->create([
            'customer_id' => $this->makeCustomer()->id,
            'currency_id' => $this->usd()->id,
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'status' => Invoice::STATUS_SENT,
            'tax_rate' => 19,
        ]);
        $invoice->items()->create(['description' => 'A', 'quantity' => 3, 'unit_price' => 50, 'discount' => 10]);
        $invoice->items()->create(['description' => 'B', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0]);

        $invoice->recalculate();

        $this->assertEquals(250.00, (float) $invoice->subtotal);
        $this->assertEquals(15.00, (float) $invoice->discount_total);
        $this->assertEquals(44.65, (float) $invoice->tax_total);
        $this->assertEquals(279.65, (float) $invoice->total);
    }

    public function test_invoice_numbers_follow_the_configured_counter(): void
    {
        Setting::set(['invoice_prefix' => 'F-', 'invoice_next_number' => 41]);

        $first = Invoice::nextNumber();
        $second = Invoice::nextNumber();

        $this->assertSame('F-00041', $first);
        $this->assertSame('F-00042', $second);
    }

    public function test_billing_command_runs_from_the_console(): void
    {
        $this->billing->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);

        $this->artisan('billing:run', ['--date' => '2026-02-01'])->assertSuccessful();

        $this->assertSame(2, Invoice::query()->count());
        $this->assertNotNull(Setting::get('last_billing_run'));
    }
}
