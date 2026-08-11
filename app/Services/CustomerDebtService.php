<?php

namespace App\Services;

use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;


class CustomerDebtService
{
    public function __construct(
        private readonly CashBoxService $cashBoxService,
         private readonly NotificationService $notifier,
    ) {}

    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return CustomerDebt::where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['customer_id'] ?? null),
                fn ($q) => $q->where('customer_id', $filters['customer_id'])
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->with(['customer'])
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

   
public function recordPayment(
    CustomerDebt $debt,
    User $user,
    array $data
): CustomerDebt {
    $result = DB::transaction(function () use ($debt, $user, $data) {

        $amount = (float) $data['amount'];

        $cashBox = $this->cashBoxService->getCashBox(
            $debt->pharmacy_id
        );

        $transaction = null;

        if ($cashBox) {
            $transaction = $this->cashBoxService->recordCustomerDebtPayment(
                $cashBox,
                $debt,
                $amount,
                $user
            );
        }

        $payment = CustomerDebtPayment::create([
            'customer_debt_id' => $debt->id,
            'cash_transaction_id' => $transaction?->id,
            'amount' => $amount,
            'payment_date' => $data['payment_date'],
            'created_by' => $user->id,
            'notes' => $data['notes'] ?? null,
        ]);

        $newPaidAmount = round(
            $debt->paid_amount + $amount,
            2
        );

        $newRemainingAmount = round(
            $debt->remaining_amount - $amount,
            2
        );

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

        $debt->load('salesInvoice');

        if ($debt->salesInvoice) {

            $invoicePaymentStatus = match (true) {
                $newRemainingAmount <= 0 => 'paid',
                $newPaidAmount > 0 => 'partial',
                default => 'unpaid',
            };

            $debt->salesInvoice->update([
                'amount_paid' => round(
                    $debt->salesInvoice->amount_paid + $amount,
                    2
                ),

                'amount_due' => max(
                    0,
                    round(
                        $debt->salesInvoice->amount_due - $amount,
                        2
                    )
                ),

                'payment_status' => $invoicePaymentStatus,
            ]);
        }

        return [
            'debt' => $debt->fresh([
                'customer',
                'payments.createdBy',
                'salesInvoice',
            ]),
            'payment' => $payment,
        ];
    });

    $debt = $result['debt'];
    $payment = $result['payment'];

    // Send notification only after successful transaction
    $pharmacy = $debt->pharmacy()
        ->with('users')
        ->first();

    if ($pharmacy) {
        foreach ($pharmacy->users as $recipient) {

            $this->notifier->sendAndSave(
                $recipient,
                'Customer Debt Payment',
                "A payment of {$payment->amount} has been made toward customer {$debt->customer->full_name}'s debt.",
                [
                    'type' => 'customer_debt_payment',
                    'pharmacy_id' => $debt->pharmacy_id,
                    'customer_debt_id' => $debt->id,
                    'payment_id' => $payment->id,
                    'customer_id' => $debt->customer_id,
                    'sales_invoice_id' => $debt->sales_invoice_id,
                    'amount' => $payment->amount,
                    'remaining_amount' => $debt->remaining_amount,
                    'status' => $debt->status,
                ]
            );
        }
    }

    return $debt;
}


}
