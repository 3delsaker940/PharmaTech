<?php

namespace App\Policies;

use App\Models\CustomerDebt;
use App\Models\User;

class CustomerDebtPolicy
{
    public function view(User $user, CustomerDebt $customerDebt): bool
    {
        return $user->pharmacy_id === $customerDebt->pharmacy_id;
    }

    public function pay(User $user, CustomerDebt $customerDebt): bool
    {
        return $user->pharmacy_id === $customerDebt->pharmacy_id;
    }
}
