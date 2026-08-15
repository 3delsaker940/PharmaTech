<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;

class SalesInvoicePolicy
{
    public function view(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->pharmacy_id === $salesInvoice->pharmacy_id;
    }

    public function update(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->pharmacy_id === $salesInvoice->pharmacy_id;
    }

    public function cancel(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->pharmacy_id === $salesInvoice->pharmacy_id;
    }
}
