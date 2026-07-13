<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\SupplierDebt;
use App\Models\SupplierDebtPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupplierDebtService
{
    public function __construct(
        private readonly CashBoxService $cashBoxService,
    ) {}
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
    public function recordPayment(
        SupplierDebt $debt,
        User         $user,
        array        $data
    ): SupplierDebt {
        return DB::transaction(function () use ($debt, $user, $data) {
            $amount = (float) $data['amount'];

            $cashBox = $this->cashBoxService->getCashBox($debt->pharmacy_id);
            $transaction = null;

            if ($cashBox) {
                $transaction = $this->cashBoxService->recordSupplierDebtPayment(
                    $cashBox,
                    $debt,
                    $amount,
                    $user
                );
            }
            SupplierDebtPayment::create([
                'supplier_debt_id' => $debt->id,
                'cash_transaction_id' => $transaction?->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaidAmount = round($debt->paid_amount + $amount, 2);
            $newRemainingAmount = round($debt->remaining_amount - $amount, 2);

            $newStatus = match (true) {
                $newRemainingAmount <= 0 => 'paid',
                $newPaidAmount > 0 => 'partial',
                default => 'open',
            };

            $debt->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => max(0, $newRemainingAmount),
                'status' => $newStatus,
            ]);
            $debt->load('purchaseInvoice');

            if ($debt->purchaseInvoice) {
                $invoicePaymentStatus = match (true) {
                    $newRemainingAmount <= 0 => 'paid',
                    $newPaidAmount > 0 => 'partial',
                    default => 'unpaid',
                };
                $debt->purchaseInvoice->update([
                    'amount_paid' => round($debt->purchaseInvoice->amount_paid + $amount, 2),
                    'amount_due' => max(0, round($debt->purchaseInvoice->amount_due - $amount, 2)),
                    'payment_status' => $invoicePaymentStatus,
                ]);
            }
            return $debt->fresh(['supplier', 'payments.createdBy', 'purchaseInvoice']);
        });
    }
}
