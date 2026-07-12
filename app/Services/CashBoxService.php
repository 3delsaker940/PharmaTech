<?php

namespace App\Services;

use App\Models\CashBox;
use App\Models\CashTransaction;
use App\Models\CustomerReturnInvoice;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SupplierReturnInvoice;
use App\Models\User;

class CashBoxService
{
    public function getCashBox(int $pharmacyId): ?CashBox
    {
        return CashBox::where('pharmacy_id', $pharmacyId)->first();
    }

//purchase invoice
    public function deductForPurchase(
        CashBox $cashBox,
        float $amount,
        PurchaseInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'      => $cashBox->id,
            'transaction_type' => 'purchase_out',
            'amount'           => $amount,
            'reference_type'   => 'purchase_invoice',
            'reference_id'     => $invoice->id,
            'created_by'       => $user->id,
            'notes'            => "Payment for purchase invoice {$invoice->invoice_number}",
            'transaction_time' => now(),
        ]);

        $cashBox->decrement('current_balance', $amount);

        return $transaction;
    }

    public function refundFromCancellation(
        CashBox $cashBox,
        PurchaseInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'      => $cashBox->id,
            'transaction_type' => 'manual_in',
            'amount'           => $invoice->amount_paid,
            'reference_type'   => 'purchase_invoice',
            'reference_id'     => $invoice->id,
            'created_by'       => $user->id,
            'notes'            => "Refund — invoice {$invoice->invoice_number} cancelled",
            'transaction_time' => now(),
        ]);

        $cashBox->increment('current_balance', $invoice->amount_paid);

        return $transaction;
    }

    //sales invoice
    public function recordForSale(
        CashBox $cashBox,
        float $amount,
        SalesInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id' => $cashBox->id,
            'transaction_type' => 'sale_in',
            'amount' => $amount,
            'reference_type' => 'sales_invoice',
            'reference_id'=> $invoice->id,
            'created_by' => $user->id,
            'notes'=> "Payment received — sales invoice {$invoice->invoice_number}",
            'transaction_time' => now(),
        ]);
        $cashBox->increment('current_balance', $amount);
        return $transaction;
    }
    public function refundFromSaleCancellation(
        CashBox $cashBox,
        SalesInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id' => $cashBox->id,
            'transaction_type' => 'customer_return_out',
            'amount' => $invoice->amount_paid,
            'reference_type'=> 'sales_invoice',
            'reference_id' => $invoice->id,
            'created_by' => $user->id,
            'notes' => "Refund — sales invoice {$invoice->invoice_number} cancelled",
            'transaction_time' => now(),
        ]);
        $cashBox->decrement('current_balance', $invoice->amount_paid);
        return $transaction;
    }

    //customer return invoice
    public function recordForCustomerReturn(
        CashBox $cashBox,
        CustomerReturnInvoice  $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'=> $cashBox->id,
            'transaction_type'=> 'customer_return_out',
            'amount'=> $invoice->refund_total,
            'reference_type'=> 'customer_return_invoice',
            'reference_id'=> $invoice->id,
            'created_by'=> $user->id,
            'notes'=> "Refund to customer — return invoice {$invoice->invoice_number}",
            'transaction_time'=> now(),
        ]);
        $cashBox->decrement('current_balance', $invoice->refund_total);
        return $transaction;
    }

    public function reverseCustomerReturn(
        CashBox $cashBox,
        CustomerReturnInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'=> $cashBox->id,
            'transaction_type'=> 'sale_in',
            'amount'=> $invoice->refund_total,
            'reference_type'=> 'customer_return_invoice',
            'reference_id'=> $invoice->id,
            'created_by'=> $user->id,
            'notes'=> "Reversal — customer return invoice {$invoice->invoice_number} cancelled",
            'transaction_time' => now(),
        ]);
        $cashBox->increment('current_balance', $invoice->refund_total);
        return $transaction;
    }

    //supplier return invoice
    public function recordForSupplierReturn(
        CashBox $cashBox,
        SupplierReturnInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'=> $cashBox->id,
            'transaction_type'=> 'manual_in',
            'amount'=> $invoice->refund_total,
            'reference_type'=> 'supplier_return_invoice',
            'reference_id'=> $invoice->id,
            'created_by'=> $user->id,
            'notes'=> "Refund from supplier — return invoice {$invoice->invoice_number}",
            'transaction_time'=> now(),
        ]);
        $cashBox->increment('current_balance', $invoice->refund_total);
        return $transaction;
    }

    public function reverseSupplierReturn(
        CashBox $cashBox,
        SupplierReturnInvoice $invoice,
        User $user
    ): CashTransaction {
        $transaction = CashTransaction::create([
            'cash_box_id'=> $cashBox->id,
            'transaction_type'=> 'purchase_out',
            'amount' => $invoice->refund_total,
            'reference_type'=> 'supplier_return_invoice',
            'reference_id'=> $invoice->id,
            'created_by'=> $user->id,
            'notes'=> "Reversal — supplier return invoice {$invoice->invoice_number} cancelled",
            'transaction_time'=> now(),
        ]);
        $cashBox->decrement('current_balance', $invoice->refund_total);
        return $transaction;
    }
}
