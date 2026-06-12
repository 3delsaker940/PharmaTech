<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\SupplierDebt;

class SupplierDebtService
{
    public function createFromInvoice(PurchaseInvoice $invoice): SupplierDebt
    {
        return SupplierDebt::create([
            'pharmacy_id'         => $invoice->pharmacy_id,
            'supplier_id'         => $invoice->supplier_id,
            'purchase_invoice_id' => $invoice->id,
            'total_amount'        => $invoice->amount_due,
            'paid_amount'         => 0,
            'remaining_amount'    => $invoice->amount_due,
            'status'              => 'open',
        ]);
    }

    public function cancelFromInvoice(PurchaseInvoice $invoice): void
    {
        SupplierDebt::where('purchase_invoice_id', $invoice->id)
            ->whereIn('status', ['open', 'partial'])
            ->update(['status' => 'cancelled']);
    }
}
