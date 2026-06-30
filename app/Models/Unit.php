<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $guarded = [];

    public function productsAsBase(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    public function productsAsSelling(): HasMany
    {
        return $this->hasMany(Product::class, 'selling_unit_id');
    }
}
