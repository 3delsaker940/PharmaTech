<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'prescription_required' => 'boolean',
        'allow_partial_selling' => 'boolean',
        'buying_price'          => 'float',
        'selling_price'         => 'float',
        'tax_rate'              => 'float',
        'discount_rate'         => 'float',
        'units_per_base'        => 'integer',
        'min_stock'             => 'integer',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function medicalInfo(): HasOne
    {
        return $this->hasOne(ProductMedicalInfo::class);
    }
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function sellingUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }

    public function scopeWithTotalQuantity($query)
    {
        return $query->withSum(
            ['stockBatches as total_quantity_sum' => fn ($q) => $q->where('status', 'active')],
            'quantity_on_hand'
        );
    }

    protected function totalQuantity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (array_key_exists('total_quantity_sum', $this->attributes)) {
                    return (int) $this->attributes['total_quantity_sum'];
                }

                return (int) $this->stockBatches()
                    ->where('status', 'active')
                    ->sum('quantity_on_hand');
            }
        );
    }
    protected function nearestExpiry(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (array_key_exists('nearest_expiry', $this->attributes)) {
                    return $this->attributes['nearest_expiry'];
                }

                return $this->stockBatches()
                    ->where('status', 'active')
                    ->whereNotNull('expiry_date')
                    ->min('expiry_date');
            }
        );
    }
}
