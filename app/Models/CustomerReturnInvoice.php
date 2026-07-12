<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReturnInvoice extends Model
{
    protected $guarded = [];
    protected $casts = [
        'invoice_date'=> 'date',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'refund_total' => 'float',
    ];
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function originalSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'original_sales_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerReturnItem::class);
    }
}
