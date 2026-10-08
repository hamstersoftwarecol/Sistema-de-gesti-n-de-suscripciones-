<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_PARTIAL,
        self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_CANCELLED,
    ];

    /** Statuses with money still to be collected. */
    public const OPEN_STATUSES = [self::STATUS_SENT, self::STATUS_PARTIAL, self::STATUS_OVERDUE];

    protected $fillable = [
        'number', 'customer_id', 'subscription_id', 'currency_id', 'exchange_rate', 'issue_date', 'due_date',
        'status', 'subtotal', 'discount_total', 'tax_rate', 'tax_total', 'total', 'amount_paid',
        'notes', 'terms', 'sent_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->number ??= static::nextNumber();
        });
    }

    /** Reserve the next invoice number using the configurable prefix and counter. */
    public static function nextNumber(): string
    {
        return DB::transaction(function () {
            $prefix = Setting::get('invoice_prefix', 'INV-');
            $counter = (int) Setting::get('invoice_next_number', 1);

            do {
                $number = $prefix.str_pad((string) $counter++, 5, '0', STR_PAD_LEFT);
            } while (static::withTrashed()->where('number', $number)->exists());

            Setting::set('invoice_next_number', $counter);

            return $number;
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_SENT => __('Sent'),
            self::STATUS_PARTIAL => __('Partially paid'),
            self::STATUS_PAID => __('Paid'),
            self::STATUS_OVERDUE => __('Overdue'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user?->isSeller()) {
            $sellerId = $user->seller?->id ?? 0;
            $query->where(fn ($q) => $q->whereHas('subscription', fn ($s) => $s->where('seller_id', $sellerId))
                ->orWhereHas('customer', fn ($c) => $c->where('seller_id', $sellerId)));
        }

        if ($user?->isCustomer()) {
            $query->where('customer_id', $user->customer_id ?? 0)->where('status', '!=', self::STATUS_DRAFT);
        }

        return $query;
    }

    public function balance(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date?->isPast() && ! $this->due_date->isToday();
    }

    public function totalInBase(): float
    {
        $rate = (float) $this->exchange_rate ?: 1;

        return (float) $this->total / $rate;
    }

    public function format(float|string|null $amount): string
    {
        return $this->currency ? $this->currency->format($amount) : number_format((float) $amount, 2);
    }

    /** Recompute subtotal, discounts, taxes and total from the line items. */
    public function recalculate(): self
    {
        $items = $this->items()->get();

        $subtotal = 0.0;
        $discount = 0.0;

        foreach ($items as $item) {
            $gross = (float) $item->quantity * (float) $item->unit_price;
            $lineDiscount = $gross * ((float) $item->discount / 100);
            $subtotal += $gross;
            $discount += $lineDiscount;
            $item->forceFill(['total' => round($gross - $lineDiscount, 2)])->saveQuietly();
        }

        $taxable = $subtotal - $discount;
        $tax = $taxable * ((float) $this->tax_rate / 100);

        $this->forceFill([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discount, 2),
            'tax_total' => round($tax, 2),
            'total' => round($taxable + $tax, 2),
        ])->save();

        return $this->refreshPaymentStatus();
    }

    /** Sync amount_paid and status with the completed payments recorded against the invoice. */
    public function refreshPaymentStatus(): self
    {
        $paid = (float) $this->payments()->where('status', Payment::STATUS_COMPLETED)->sum('amount');

        $status = $this->status;

        if ($status !== self::STATUS_CANCELLED) {
            if ($paid > 0 && $paid + 0.004 >= (float) $this->total) {
                $status = self::STATUS_PAID;
            } elseif ($paid > 0) {
                $status = self::STATUS_PARTIAL;
            } elseif (in_array($status, [self::STATUS_PAID, self::STATUS_PARTIAL], true)) {
                $status = $this->due_date?->isPast() && ! $this->due_date->isToday() ? self::STATUS_OVERDUE : self::STATUS_SENT;
            }
        }

        $this->forceFill([
            'amount_paid' => round($paid, 2),
            'status' => $status,
            'paid_at' => $status === self::STATUS_PAID ? ($this->paid_at ?? now()) : null,
        ])->save();

        // A fully paid renewal invoice brings a past-due subscription back to active.
        if ($status === self::STATUS_PAID && $this->subscription && $this->subscription->status === Subscription::STATUS_PAST_DUE) {
            $hasOtherOverdue = $this->subscription->invoices()
                ->whereKeyNot($this->id)
                ->where('status', self::STATUS_OVERDUE)
                ->exists();

            if (! $hasOtherOverdue) {
                $this->subscription->update(['status' => Subscription::STATUS_ACTIVE]);
            }
        }

        return $this;
    }
}
