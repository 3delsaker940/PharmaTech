<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacy extends Model
{
    protected $guarded = [];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function cashBoxes(): HasMany
    {
        return $this->hasMany(CashBox::class);
    }

    public function supplierDebts(): HasMany
    {
        return $this->hasMany(SupplierDebt::class);
    }
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
    public function customerDebts(): HasMany
    {
        return $this->hasMany(CustomerDebt::class);
    }
}
