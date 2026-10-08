<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    use Concerns\LogsActivity;

    protected $fillable = ['code', 'name', 'symbol', 'exchange_rate', 'decimal_places', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'decimal_places' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('default_currency'));
        static::deleted(fn () => Cache::forget('default_currency'));
    }

    /** The base currency every report is converted to. */
    public static function default(): ?self
    {
        return Cache::remember('default_currency', 3600, function () {
            return static::query()->where('is_default', true)->first() ?? static::query()->first();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function makeDefault(): void
    {
        static::query()->whereKeyNot($this->getKey())->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'exchange_rate' => 1])->save();
    }

    /** Convert an amount expressed in this currency to the base currency. */
    public function toBase(float|string|null $amount): float
    {
        $rate = (float) $this->exchange_rate ?: 1;

        return (float) $amount / $rate;
    }

    /** Convert an amount expressed in the base currency to this currency. */
    public function fromBase(float|string|null $amount): float
    {
        return (float) $amount * ((float) $this->exchange_rate ?: 1);
    }

    public function format(float|string|null $amount): string
    {
        $formatted = number_format((float) $amount, $this->decimal_places, '.', ',');

        return $this->symbol.$formatted;
    }

    public function label(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
