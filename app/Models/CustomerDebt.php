<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDebt extends Model
{
    protected $guarded = [];
    protected $casts = [
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'remaining_amount' => 'float',
        'due_date' => 'date',
    ];
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
