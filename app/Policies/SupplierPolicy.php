<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->pharmacy_id === $supplier->pharmacy_id;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->pharmacy_id === $supplier->pharmacy_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->pharmacy_id === $supplier->pharmacy_id;
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->pharmacy_id === $supplier->pharmacy_id;
    }
}
