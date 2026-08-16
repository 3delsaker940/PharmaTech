<?php

namespace App\Policies;

use App\Models\SupplierReturnInvoice;
use App\Models\User;

class SupplierReturnInvoicePolicy
{
    public function view(User $user, SupplierReturnInvoice $supplierReturnInvoice): bool
    {
        return $user->pharmacy_id === $supplierReturnInvoice->pharmacy_id;
    }

    public function cancel(User $user, SupplierReturnInvoice $supplierReturnInvoice): bool
    {
        return $user->pharmacy_id === $supplierReturnInvoice->pharmacy_id;
    }
}
