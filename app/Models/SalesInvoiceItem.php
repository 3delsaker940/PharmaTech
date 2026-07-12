<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    protected $guarded = [];
    protected $casts = [
        'quantity' => 'integer',
        'selling_price' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'line_total' => 'float',
    ];
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
