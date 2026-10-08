<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity, HasFactory, SoftDeletes;

    public const STATUSES = ['lead', 'active', 'inactive'];

    protected $fillable = [
        'code', 'name', 'company', 'email', 'phone', 'tax_id', 'address', 'city', 'state',
        'postal_code', 'country', 'currency_id', 'seller_id', 'status', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->code ??= static::nextCode();
        });
    }

    public static function nextCode(): string
    {
        $next = (int) static::withTrashed()->max('id') + 1;

        do {
            $code = 'CUS-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public static function statusOptions(): array
    {
        return [
            'lead' => __('Lead'),
            'active' => __('Active'),
            'inactive' => __('Inactive'),
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function displayName(): string
    {
        return $this->company ? "{$this->name} ({$this->company})" : $this->name;
    }

    public function outstandingBalance(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT])
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->balance());
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user?->isSeller()) {
            $query->where('seller_id', $user->seller?->id ?? 0);
        }

        return $query;
    }
}
