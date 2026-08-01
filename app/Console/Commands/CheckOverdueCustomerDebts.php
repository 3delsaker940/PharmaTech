<?php

namespace App\Console\Commands;

use App\Models\CustomerDebt;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-overdue-customer-debts')]
#[Description('Check for overdue customer debts and send notifications to pharmacy owners')]
class CheckOverdueCustomerDebts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notifier)
    {
        $overdueDebts = CustomerDebt::where('due_date', '<', today())
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->with(['pharmacy.owner', 'customer'])
            ->get();

        foreach ($overdueDebts as $debt) {
            $debt->update(['status' => 'overdue']);

            $notifier->sendAndSave(
                $debt->pharmacy->owner,
                'Overdue Debt Payment ⚠️',
                "Customer {$debt->customer->name} has passed the payment due date.",
                ['customer_debt_id' => $debt->id]
            );
        }
    }
}
