<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Plan extends Model
{
    use Concerns\LogsActivity;

    public const CYCLES = ['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'yearly'];

    protected $fillable = [
        'product_id', 'currency_id', 'name', 'price', 'billing_cycle', 'interval_count',
        'trial_days', 'setup_fee', 'features', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'interval_count' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function cycleOptions(): array
    {
        return [
            'daily' => __('Daily'),
            'weekly' => __('Weekly'),
            'monthly' => __('Monthly'),
            'quarterly' => __('Quarterly'),
            'semiannual' => __('Semiannual'),
            'yearly' => __('Yearly'),
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function fullName(): string
    {
        return ($this->product?->name ? $this->product->name.' — ' : '').$this->name;
    }

    public function cycleLabel(): string
    {
        $label = static::cycleOptions()[$this->billing_cycle] ?? $this->billing_cycle;

        return $this->interval_count > 1
            ? __('Every :count × :cycle', ['count' => $this->interval_count, 'cycle' => mb_strtolower($label)])
            : $label;
    }

    /** Length of one billing period expressed in months (used to normalise MRR). */
    public function monthsPerPeriod(): float
    {
        $months = match ($this->billing_cycle) {
            'daily' => 1 / 30,
            'weekly' => 12 / 52,
            'monthly' => 1,
            'quarterly' => 3,
            'semiannual' => 6,
            'yearly' => 12,
            default => 1,
        };

        return $months * max(1, $this->interval_count);
    }

    /** Move a date forward by the given number of billing periods. */
    public function addPeriods(Carbon|string $date, int $periods = 1): Carbon
    {
        $date = Carbon::parse($date)->copy();
        $n = max(1, $this->interval_count) * $periods;

        return match ($this->billing_cycle) {
            'daily' => $date->addDays($n),
            'weekly' => $date->addWeeks($n),
            'quarterly' => $date->addMonthsNoOverflow(3 * $n),
            'semiannual' => $date->addMonthsNoOverflow(6 * $n),
            'yearly' => $date->addYearsNoOverflow($n),
            default => $date->addMonthsNoOverflow($n),
        };
    }

    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->features))));
    }
}
