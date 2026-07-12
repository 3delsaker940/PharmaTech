<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnItem extends Model
{
    protected $guarded = [];
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'line_total' => 'float',
    ];
    public function customerReturnInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomerReturnInvoice::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
