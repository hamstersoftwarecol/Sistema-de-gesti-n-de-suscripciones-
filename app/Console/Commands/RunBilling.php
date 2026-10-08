<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RunBilling extends Command
{
    protected $signature = 'billing:run {--date= : Run the engine as if today were this date (Y-m-d)}';

    protected $description = 'Recurring billing engine: renew subscriptions, issue invoices, expire plans and flag overdue invoices';

    public function handle(BillingService $billing): int
    {
        // --date simulates the whole clock so timestamps and de-duplication stay consistent.
        if ($this->option('date')) {
            Carbon::setTestNow(Carbon::parse($this->option('date'))->setTimeFrom(now()));
        }

        $date = today();

        $summary = $billing->run($date);

        $this->table(['Renewed', 'Invoices', 'Expired', 'Overdue', 'Past due'], [array_values($summary)]);

        return self::SUCCESS;
    }
}
