<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasTranslations;
    protected $fillable = ['name', 'governorate_id'];
    public $translatable = ['name'];
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
