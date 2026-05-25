<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;
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

    public function ownedPharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class);
    }

    public function pharmacies(): BelongsToMany
    {
        return $this->belongsToMany(Pharmacy::class, 'pharmacy_users')
            ->withPivot(['membership_role', 'status', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    public function pharmacyMemberships(): HasMany
    {
        return $this->hasMany(PharmacyUser::class);
    }

    public function isActiveIn(Pharmacy $pharmacy): bool
    {
        return $this->pharmacyMemberships()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('status', 'active')
            ->exists();
    }

    public function ownsPharmacy(Pharmacy $pharmacy): bool
    {
        return $pharmacy->user_id === $this->id;
    }
}
