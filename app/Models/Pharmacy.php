<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pharmacy extends Model
{
    protected $guarded = [];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
