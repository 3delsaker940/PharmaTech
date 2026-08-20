<?php

namespace App\Models;

use App\Concerns\HasHashedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, HasHashedFields;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'address' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Supplier $supplier) {
            if ($supplier->isDirty('phone')) {
                $supplier->phone_hash = static::hashForLookup(static::normalizePhone($supplier->phone));
            }
        });
    }

    /**
     * Find suppliers within a pharmacy by exact phone match
     * (use instead of where('phone', 'like', ...), which cannot
     * work on an encrypted column).
     */
    public static function findByPhone(int $pharmacyId, string $phone)
    {
        return static::where('pharmacy_id', $pharmacyId)
            ->where('phone_hash', static::hashForLookup(static::normalizePhone($phone)))
            ->get();
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }
    public function debts(): HasMany
    {
        return $this->hasMany(SupplierDebt::class);
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
