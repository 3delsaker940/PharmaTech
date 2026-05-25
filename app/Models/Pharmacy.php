<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacy extends Model
{
    protected $guarded = [];
    protected $casts = [
        'status' => 'string',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pharmacy_users')
            ->withPivot(['membership_role', 'status', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }
    public function memberships(): HasMany
    {
        return $this->hasMany(PharmacyUser::class);
    }
}
