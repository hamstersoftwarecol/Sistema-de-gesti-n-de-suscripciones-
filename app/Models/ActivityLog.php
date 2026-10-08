<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Throwable;

class ActivityLog extends Model
{
    /** Allows seeders and imports to switch logging off. */
    public static bool $enabled = true;

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'properties', 'ip_address'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): ?self
    {
        if (! static::$enabled) {
            return null;
        }

        try {
            return static::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description ?? $action,
                'properties' => $properties ?: null,
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            ]);
        } catch (Throwable) {
            return null; // Never let auditing break the main operation.
        }
    }
}
