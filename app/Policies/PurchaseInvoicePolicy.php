<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\User;

class PurchaseInvoicePolicy
{
    public function view(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->pharmacy_id === $purchaseInvoice->pharmacy_id;
    }

    public function update(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->pharmacy_id === $purchaseInvoice->pharmacy_id;
    }

    public function cancel(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->pharmacy_id === $purchaseInvoice->pharmacy_id;
    }
}
