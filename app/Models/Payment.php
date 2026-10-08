<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Concerns\LogsActivity;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [self::STATUS_COMPLETED, self::STATUS_PENDING, self::STATUS_FAILED, self::STATUS_REFUNDED];

    public const METHODS = ['cash', 'bank_transfer', 'card', 'paypal', 'stripe', 'other'];

    protected $fillable = [
        'reference', 'invoice_id', 'customer_id', 'currency_id', 'amount', 'method', 'status',
        'paid_at', 'transaction_id', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->reference ??= static::nextReference();
        });

        $sync = function (Payment $payment) {
            $invoiceIds = array_filter(array_unique([$payment->invoice_id, $payment->getOriginal('invoice_id')]));

            Invoice::query()->whereIn('id', $invoiceIds)->get()->each->refreshPaymentStatus();
        };

        static::saved($sync);
        static::deleted($sync);
    }

    public static function nextReference(): string
    {
        $next = (int) static::query()->max('id') + 1;

        do {
            $reference = 'PAY-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_REFUNDED => __('Refunded'),
        ];
    }

    public static function methodOptions(): array
    {
        return [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank transfer'),
            'card' => __('Credit / debit card'),
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'other' => __('Other'),
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user?->isSeller()) {
            $sellerId = $user->seller?->id ?? 0;
            $query->whereHas('customer', fn ($c) => $c->where('seller_id', $sellerId));
        }

        if ($user?->isCustomer()) {
            $query->where('customer_id', $user->customer_id ?? 0);
        }

        return $query;
    }

    public function baseAmount(): float
    {
        return $this->currency ? $this->currency->toBase($this->amount) : (float) $this->amount;
    }

    public function format(): string
    {
        return $this->currency ? $this->currency->format($this->amount) : number_format((float) $this->amount, 2);
    }
}
