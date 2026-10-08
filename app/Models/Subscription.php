<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity, HasFactory, SoftDeletes;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_TRIAL, self::STATUS_ACTIVE, self::STATUS_PAST_DUE,
        self::STATUS_PAUSED, self::STATUS_CANCELLED, self::STATUS_EXPIRED,
    ];

    /** Statuses that count as a live subscription (MRR, renewals, reminders). */
    public const LIVE_STATUSES = [self::STATUS_TRIAL, self::STATUS_ACTIVE, self::STATUS_PAST_DUE];

    protected $fillable = [
        'reference', 'customer_id', 'plan_id', 'seller_id', 'currency_id', 'price', 'quantity', 'discount',
        'status', 'start_date', 'trial_ends_at', 'current_period_start', 'current_period_end',
        'next_billing_date', 'ends_at', 'auto_renew', 'cancelled_at', 'cancel_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'quantity' => 'integer',
            'start_date' => 'date',
            'trial_ends_at' => 'date',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'next_billing_date' => 'date',
            'ends_at' => 'date',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription) {
            $subscription->reference ??= static::nextReference();
        });
    }

    public static function nextReference(): string
    {
        $next = (int) static::withTrashed()->max('id') + 1;

        do {
            $reference = 'SUB-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_TRIAL => __('Trial'),
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_PAST_DUE => __('Past due'),
            self::STATUS_PAUSED => __('Paused'),
            self::STATUS_CANCELLED => __('Cancelled'),
            self::STATUS_EXPIRED => __('Expired'),
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class)->latest('period_start');
    }

    public function scopeLive($query)
    {
        return $query->whereIn('status', self::LIVE_STATUSES);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user?->isSeller()) {
            $sellerId = $user->seller?->id ?? 0;
            $query->where(fn ($q) => $q->where('seller_id', $sellerId)
                ->orWhereHas('customer', fn ($c) => $c->where('seller_id', $sellerId)));
        }

        if ($user?->isCustomer()) {
            $query->where('customer_id', $user->customer_id ?? 0);
        }

        return $query;
    }

    public function isLive(): bool
    {
        return in_array($this->status, self::LIVE_STATUSES, true);
    }

    /** Amount charged every billing period (after quantity and discount). */
    public function periodAmount(): float
    {
        $gross = (float) $this->price * max(1, $this->quantity);

        return round($gross * (1 - ((float) $this->discount / 100)), 2);
    }

    /** Monthly recurring revenue contributed by this subscription, in its own currency. */
    public function monthlyAmount(): float
    {
        $months = $this->plan?->monthsPerPeriod() ?: 1;

        return $this->periodAmount() / $months;
    }

    public function monthlyAmountInBase(): float
    {
        return $this->currency ? $this->currency->toBase($this->monthlyAmount()) : $this->monthlyAmount();
    }

    public function daysUntilRenewal(): ?int
    {
        return $this->next_billing_date ? (int) now()->startOfDay()->diffInDays($this->next_billing_date, false) : null;
    }
}
