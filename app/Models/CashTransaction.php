<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'           => 'float',
        'transaction_time' => 'datetime',
    ];

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierDebtPayment(): HasOne
    {
        return $this->hasOne(SupplierDebtPayment::class);
    }
}
