<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
    public function debts(): HasMany
    {
        return $this->hasMany(CustomerDebt::class);
    }
}
