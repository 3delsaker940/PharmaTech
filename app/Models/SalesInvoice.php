<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesInvoice extends Model
{
    protected $guarded = [];
    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'grand_total' => 'float',
        'amount_paid' => 'float',
        'amount_due' => 'float',
    ];
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }
    public function customerDebt(): HasOne
    {
        return $this->hasOne(CustomerDebt::class);
    }
}
