<?php

namespace App\Policies;

use App\Models\CustomerReturnInvoice;
use App\Models\User;

class CustomerReturnInvoicePolicy
{
    public function view(User $user, CustomerReturnInvoice $customerReturnInvoice): bool
    {
        return $user->pharmacy_id === $customerReturnInvoice->pharmacy_id;
    }

    public function cancel(User $user, CustomerReturnInvoice $customerReturnInvoice): bool
    {
        return $user->pharmacy_id === $customerReturnInvoice->pharmacy_id;
    }
}
