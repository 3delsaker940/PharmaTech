<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }
    public function refreshTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\RefreshToken::class);
    }
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
    public function purchaseInvoicesCreated(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'created_by');
    }

    public function stockMovementsCreated(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function cashTransactionsCreated(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'created_by');
    }
    public function notifications()
    {
        return $this->hasMany(AppNotification::class);
    }
}
