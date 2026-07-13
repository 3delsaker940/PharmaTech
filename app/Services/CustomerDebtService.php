<?php

namespace App\Services;

use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerDebtService
{
    public function __construct(
        private readonly CashBoxService $cashBoxService,
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
        return DB::transaction(function () use ($debt, $user, $data) {
            $amount = (float) $data['amount'];

            $cashBox = $this->cashBoxService->getCashBox($debt->pharmacy_id);

            $transaction = null;
            if ($cashBox) {
                $transaction = $this->cashBoxService->recordCustomerDebtPayment(
                    $cashBox,
                    $debt,
                    $amount,
                    $user
                );
            }

            CustomerDebtPayment::create([
                'customer_debt_id' => $debt->id,
                'cash_transaction_id'=> $transaction?->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaidAmount     = round($debt->paid_amount + $amount, 2);
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
            $debt->load('salesInvoice');

            if ($debt->salesInvoice) {
                $invoicePaymentStatus = match (true) {
                    $newRemainingAmount <= 0 => 'paid',
                    $newPaidAmount > 0 => 'partial',
                    default => 'unpaid',
                };
                $debt->salesInvoice->update([
                    'amount_paid' => round($debt->salesInvoice->amount_paid + $amount, 2),
                    'amount_due' => max(0, round($debt->salesInvoice->amount_due - $amount, 2)),
                    'payment_status' => $invoicePaymentStatus,
                ]);
            }
            return $debt->fresh(['customer', 'payments.createdBy', 'salesInvoice']);
        });
    }
}
