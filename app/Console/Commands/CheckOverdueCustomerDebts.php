<?php

namespace App\Console\Commands;

use App\Models\CustomerDebt;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-overdue-customer-debts')]
#[Description('Check for overdue customer debts and notify pharmacy users')]
class CheckOverdueCustomerDebts extends Command
{
    public function handle(NotificationService $notifier)
    {
        $overdueDebts = CustomerDebt::where('due_date', '<', today())
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->with(['pharmacy.users', 'customer'])
            ->get();

        foreach ($overdueDebts as $debt) {
            $debt->update(['status' => 'overdue']);

            if (!$debt->pharmacy || $debt->pharmacy->users->isEmpty()) {
                continue;
            }

            foreach ($debt->pharmacy->users as $recipient) {
                $notifier->sendAndSave(
                    $recipient,
                    'Overdue Debt Payment ⚠️',
                    "Customer {$debt->customer->full_name} has passed the payment due date.",
                    ['customer_debt_id' => $debt->id]
                );
            }
        }
    }
}
