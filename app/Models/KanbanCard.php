<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCard extends Model
{
    use Concerns\LogsActivity;

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'kanban_column_id', 'title', 'description', 'priority', 'due_date', 'assigned_to',
        'customer_id', 'subscription_id', 'created_by', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low' => __('Low'),
            'medium' => __('Medium'),
            'high' => __('High'),
            'urgent' => __('Urgent'),
        ];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class, 'kanban_column_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast() && ! $this->due_date->isToday()
            && ! $this->column?->is_done_column;
    }
}
