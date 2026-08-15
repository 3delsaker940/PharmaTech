<?php

namespace App\Policies;

use App\Models\SupplierDebt;
use App\Models\User;

class SupplierDebtPolicy
{
    public function view(User $user, SupplierDebt $supplierDebt): bool
    {
        return $user->pharmacy_id === $supplierDebt->pharmacy_id;
    }

    public function pay(User $user, SupplierDebt $supplierDebt): bool
    {
        return $user->pharmacy_id === $supplierDebt->pharmacy_id;
    }
}
