<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use Concerns\LogsActivity;

    protected $fillable = ['name', 'description', 'color'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
