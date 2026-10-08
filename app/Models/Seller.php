<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Seller extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity;

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'commission_rate', 'monthly_target', 'is_active', 'notes'];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'monthly_target' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Completed payments attributed to this seller (through the subscription, or the customer when the invoice has none). */
    public function attributedPayments(?Carbon $from = null, ?Carbon $to = null)
    {
        return Payment::query()
            ->with('currency')
            ->where('status', Payment::STATUS_COMPLETED)
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->where(function ($q) {
                $q->whereHas('invoice.subscription', fn ($s) => $s->where('seller_id', $this->id))
                    ->orWhere(function ($q) {
                        $q->where(fn ($q) => $q->whereNull('invoice_id')->orWhereHas('invoice', fn ($i) => $i->whereNull('subscription_id')))
                            ->whereHas('customer', fn ($c) => $c->where('seller_id', $this->id));
                    });
            });
    }

    /** Revenue (base currency) collected by this seller in the given period. */
    public function revenueBetween(?Carbon $from = null, ?Carbon $to = null): float
    {
        return $this->attributedPayments($from, $to)->get()
            ->sum(fn (Payment $p) => $p->baseAmount());
    }

    public function commissionBetween(?Carbon $from = null, ?Carbon $to = null): float
    {
        return round($this->revenueBetween($from, $to) * ((float) $this->commission_rate / 100), 2);
    }
}
