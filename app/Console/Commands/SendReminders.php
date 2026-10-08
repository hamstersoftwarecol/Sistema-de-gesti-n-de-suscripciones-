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
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();

        $summary = $reminders->run($date);

        $this->table(['Renewal reminders', 'Overdue notices', 'Failed'], [array_values($summary)]);

        return self::SUCCESS;
    }
}
