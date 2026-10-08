<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    public static function cycles(): array
    {
        return [
            'daily' => ['daily', 1, '2026-01-31', '2026-02-01', 1 / 30],
            'weekly' => ['weekly', 2, '2026-01-31', '2026-02-14', 24 / 52],
            'monthly without overflow' => ['monthly', 1, '2026-01-31', '2026-02-28', 1],
            'quarterly' => ['quarterly', 1, '2026-11-30', '2027-02-28', 3],
            'semiannual' => ['semiannual', 1, '2026-08-31', '2027-02-28', 6],
            'yearly leap day' => ['yearly', 1, '2028-02-29', '2029-02-28', 12],
        ];
    }

    #[DataProvider('cycles')]
    public function test_periods_advance_without_month_overflow(string $cycle, int $interval, string $from, string $expected, float $months): void
    {
        $plan = new Plan(['billing_cycle' => $cycle, 'interval_count' => $interval]);

        $this->assertSame($expected, $plan->addPeriods(Carbon::parse($from))->toDateString());
        $this->assertEqualsWithDelta($months, $plan->monthsPerPeriod(), 0.0001);
    }

    public function test_subscription_amounts(): void
    {
        $plan = new Plan(['billing_cycle' => 'yearly', 'interval_count' => 1]);
        $subscription = new Subscription(['price' => 1200, 'quantity' => 3, 'discount' => 25]);
        $subscription->setRelation('plan', $plan);

        $this->assertEquals(2700.0, $subscription->periodAmount());
        $this->assertEquals(225.0, $subscription->monthlyAmount());
    }
}
