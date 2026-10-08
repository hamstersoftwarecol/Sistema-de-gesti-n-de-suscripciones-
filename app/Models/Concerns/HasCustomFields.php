<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Gives a model user-defined fields (configured in Settings → Custom fields).
 */
trait HasCustomFields
{
    public static function bootHasCustomFields(): void
    {
        static::deleting(function ($model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->customFieldValues()->delete();
            }
        });
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    /** @return Collection<int, CustomField> */
    public static function customFields(): Collection
    {
        return CustomField::for((new static)->getMorphClass());
    }

    public static function customFieldRules(): array
    {
        $rules = [];

        foreach (static::customFields() as $field) {
            $rules['custom_fields.'.$field->name] = $field->rules();
        }

        return $rules;
    }

    public function customFieldValue(CustomField|string $field): ?string
    {
        $fieldId = $field instanceof CustomField
            ? $field->id
            : static::customFields()->firstWhere('name', $field)?->id;

        return $this->customFieldValues->firstWhere('custom_field_id', $fieldId)?->value;
    }

    public function saveCustomFields(?array $input): void
    {
        $input ??= [];

        foreach (static::customFields() as $field) {
            $value = $input[$field->name] ?? null;

            if ($field->type === 'checkbox') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                ['value' => is_array($value) ? implode(', ', $value) : $value],
            );
        }

        $this->unsetRelation('customFieldValues');
    }
}
