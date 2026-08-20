<?php

namespace App\Models;

use App\Concerns\HasHashedFields;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\HasName;
use Spatie\Permission\Traits\HasRoles;

#[Hidden(['password', 'remember_token', 'email_hash', 'phone_hash'])]
class User extends Authenticatable implements MustVerifyEmail, FilamentUser, HasName
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, HasHashedFields;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'email' => 'encrypted',
            'phone_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email')) {
                $user->email_hash = static::hashForLookup(static::normalizeEmail($user->email));
            }
            if ($user->isDirty('phone_number')) {
                $user->phone_hash = static::hashForLookup(static::normalizePhone($user->phone_number));
            }
        });
    }

    /**
     * Find a user by their plaintext email (use this instead of
     * where('email', ...), which cannot match encrypted values).
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email_hash', static::hashForLookup(static::normalizeEmail($email)))->first();
    }

    /**
     * Find a user by their plaintext phone number (any accepted format).
     */
    public static function findByPhone(string $phone): ?self
    {
        return static::where('phone_hash', static::hashForLookup(static::normalizePhone($phone)))->first();
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('system_admin') && $this->status === 'active';
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
