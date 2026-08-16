<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->pharmacy_id === $stockMovement->pharmacy_id;
    }
}
