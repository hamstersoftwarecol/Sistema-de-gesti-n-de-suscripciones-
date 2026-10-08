<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity;

    public const TYPES = ['service', 'software', 'digital', 'physical'];

    protected $fillable = ['product_category_id', 'supplier_id', 'name', 'sku', 'type', 'description', 'cost', 'is_active'];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'service' => __('Service'),
            'software' => __('Software / SaaS'),
            'digital' => __('Digital'),
            'physical' => __('Physical'),
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
