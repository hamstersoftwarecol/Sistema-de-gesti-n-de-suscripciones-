<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use Concerns\HasCustomFields, Concerns\LogsActivity;

    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'website', 'tax_id', 'address', 'country', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
