<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColumn extends Model
{
    use Concerns\LogsActivity;

    protected $fillable = ['name', 'color', 'sort_order', 'is_done_column'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_done_column' => 'boolean',
        ];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(KanbanCard::class)->orderBy('sort_order');
    }
}
