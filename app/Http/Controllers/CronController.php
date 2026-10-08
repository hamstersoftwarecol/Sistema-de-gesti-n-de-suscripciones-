<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\BillingService;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;

/**
 * Web cron for shared hosting without crontab access: GET /cron/{token}
 */
class CronController extends Controller
{
    public function __invoke(string $token, BillingService $billing, ReminderService $reminders): JsonResponse
    {
        $expected = (string) Setting::get('cron_token');

        abort_unless($expected !== '' && hash_equals($expected, $token), 403);

        $billingSummary = $billing->run();
        $reminderSummary = $reminders->run();

        Setting::set('last_cron_run', now()->toDateTimeString());

        return response()->json([
            'ok' => true,
            'ran_at' => now()->toDateTimeString(),
            'billing' => $billingSummary,
            'reminders' => $reminderSummary,
        ]);
    }
}
