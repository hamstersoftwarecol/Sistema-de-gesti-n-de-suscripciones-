<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Writes an audit trail entry whenever the model is created, updated or deleted.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->logActivity('created'));

        static::updated(function ($model) {
            $changes = Arr::except($model->getChanges(), ['updated_at', 'password', 'remember_token']);

            if ($changes !== []) {
                $model->logActivity('updated', ['changes' => array_keys($changes)]);
            }
        });

        static::deleted(fn ($model) => $model->logActivity('deleted'));
    }

    protected function logActivity(string $action, array $properties = []): void
    {
        $label = Str::headline(class_basename($this));
        $identifier = $this->reference ?? $this->number ?? $this->code ?? $this->name ?? $this->title ?? ('#'.$this->getKey());

        ActivityLog::record($action, $this, "{$label} {$identifier}", $properties);
    }
}
