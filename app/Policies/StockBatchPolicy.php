<?php

namespace App\Policies;

use App\Models\StockBatch;
use App\Models\User;

class StockBatchPolicy
{
    public function view(User $user, StockBatch $stockBatch): bool
    {
        return $user->pharmacy_id === $stockBatch->pharmacy_id;
    }

    public function markExpired(User $user, StockBatch $stockBatch): bool
    {
        return $user->pharmacy_id === $stockBatch->pharmacy_id;
    }
}
