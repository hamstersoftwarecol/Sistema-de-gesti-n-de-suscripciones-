<?php

namespace Tests\Feature;

use App\Mail\TemplateMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsBillingData;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use BuildsBillingData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        Mail::fake();
    }

    public function test_renewal_reminders_are_sent_once_per_configured_day(): void
    {
        Setting::set('reminder_days', '7,3');
        $subscription = app(BillingService::class)->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);
        $this->assertSame('2026-02-01', $subscription->next_billing_date->toDateString());

        $reminders = app(ReminderService::class);

        Carbon::setTestNow('2026-01-20 09:00');
        $this->assertSame(0, $reminders->run()['renewal']);
        Carbon::setTestNow('2026-01-25 09:00');
        $this->assertSame(1, $reminders->run()['renewal']);
        // Same day again: no duplicate.
        Carbon::setTestNow('2026-01-25 18:00');
        $this->assertSame(0, $reminders->run()['renewal']);
        Carbon::setTestNow('2026-01-29 09:00');
        $this->assertSame(1, $reminders->run()['renewal']);
        Carbon::setTestNow();

        Mail::assertSent(TemplateMail::class, 2);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->hasTo('ada@example.com')
            && str_contains($mail->subjectLine, 'Pro monthly'));

        $this->assertSame(2, EmailLog::query()->where('template_key', EmailTemplate::RENEWAL_REMINDER)->where('status', 'sent')->count());
    }

    public function test_overdue_notices_repeat_on_the_configured_interval(): void
    {
        Setting::set(['overdue_reminder_interval' => 3, 'reminder_days' => '1']);
        $subscription = app(BillingService::class)->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => now()->subDays(20)->toDateString(),
            'auto_renew' => false,
        ]);
        app(BillingService::class)->run(today());

        $reminders = app(ReminderService::class);
        $this->assertSame(1, $reminders->run()['overdue']);
        Carbon::setTestNow(now()->addDay());
        $this->assertSame(0, $reminders->run()['overdue']);
        Carbon::setTestNow(now()->addDays(2));
        $this->assertSame(1, $reminders->run()['overdue']);
        Carbon::setTestNow();

        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => count($mail->files) === 1
            && str_ends_with($mail->files[0]['name'], '.pdf'));
        $this->assertSame(2, EmailLog::query()->where('template_key', EmailTemplate::INVOICE_OVERDUE)->where('invoice_id', $subscription->invoices()->first()->id)->count());
    }

    public function test_reminders_can_be_disabled(): void
    {
        Setting::set('reminders_enabled', false);

        $this->assertSame(['renewal' => 0, 'overdue' => 0, 'failed' => 0], app(ReminderService::class)->run());
        Mail::assertNothingSent();
    }

    public function test_disabled_template_is_not_sent(): void
    {
        EmailTemplate::query()->where('key', EmailTemplate::RENEWAL_REMINDER)->update(['is_active' => false]);
        Setting::set('reminder_days', '7');

        app(BillingService::class)->subscribe([
            'customer_id' => $this->makeCustomer()->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => '2026-01-01',
        ]);

        Carbon::setTestNow('2026-01-25');
        $this->assertSame(0, app(ReminderService::class)->run()['renewal']);
        Carbon::setTestNow();
        Mail::assertNothingSent();
    }

    public function test_template_placeholders_are_rendered(): void
    {
        $template = EmailTemplate::query()->where('key', EmailTemplate::PAYMENT_RECEIVED)->firstOrFail();

        $rendered = $template->render(['customer_name' => 'Ada', 'payment_amount' => '$10.00', 'company_name' => 'ACME']);

        $this->assertStringContainsString('$10.00', $rendered['subject']);
        $this->assertStringContainsString('Hi Ada', $rendered['body']);
        $this->assertStringContainsString('ACME', $rendered['body']);
    }

    public function test_reminder_command_runs(): void
    {
        $this->artisan('reminders:send')->assertSuccessful();
        $this->assertNotNull(Setting::get('last_reminder_run'));
    }
}
