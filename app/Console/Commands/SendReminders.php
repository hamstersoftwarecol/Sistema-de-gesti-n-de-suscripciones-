<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReminders extends Command
{
    protected $signature = 'reminders:send {--date= : Run as if today were this date (Y-m-d)}';

    protected $description = 'Send renewal reminders and overdue invoice notices by e-mail';

    public function handle(ReminderService $reminders): int
    {
        // --date simulates the whole clock so timestamps and de-duplication stay consistent.
        if ($this->option('date')) {
            Carbon::setTestNow(Carbon::parse($this->option('date'))->setTimeFrom(now()));
        }

        $date = today();

        $summary = $reminders->run($date);

        $this->table(['Renewal reminders', 'Overdue notices', 'Failed'], [array_values($summary)]);

        return self::SUCCESS;
    }
}
