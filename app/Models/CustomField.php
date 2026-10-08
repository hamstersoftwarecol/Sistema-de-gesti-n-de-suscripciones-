<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CustomField extends Model
{
    use Concerns\LogsActivity;

    public const TYPES = ['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'];

    protected $fillable = ['entity', 'name', 'label', 'type', 'options', 'placeholder', 'is_required', 'show_in_table', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'show_in_table' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Entities that support custom fields, keyed by morph alias. */
    public static function entityOptions(): array
    {
        return [
            'customer' => __('Customers'),
            'subscription' => __('Subscriptions'),
            'invoice' => __('Invoices'),
            'product' => __('Products'),
            'supplier' => __('Suppliers'),
            'seller' => __('Sellers'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'text' => __('Text'),
            'textarea' => __('Long text'),
            'number' => __('Number'),
            'date' => __('Date'),
            'email' => __('Email'),
            'url' => 'URL',
            'select' => __('Dropdown list'),
            'checkbox' => __('Checkbox (yes/no)'),
        ];
    }

    /** @return Collection<int, CustomField> */
    public static function for(string $entity): Collection
    {
        return static::query()
            ->where('entity', $entity)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /** Laravel validation rules for this field. */
    public function rules(): array
    {
        $rules = [$this->is_required && $this->type !== 'checkbox' ? 'required' : 'nullable'];

        $rules = array_merge($rules, match ($this->type) {
            'number' => ['numeric'],
            'date' => ['date'],
            'email' => ['email'],
            'url' => ['url'],
            'select' => [Rule::in($this->options ?? [])],
            'checkbox' => ['boolean'],
            'textarea' => ['string', 'max:5000'],
            default => ['string', 'max:255'],
        });

        return $rules;
    }

    public function displayValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($this->type) {
            'checkbox' => $value ? __('Yes') : __('No'),
            default => $value,
        };
    }
}
