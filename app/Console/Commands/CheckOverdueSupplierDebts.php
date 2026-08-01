<?php

namespace App\Console\Commands;

use App\Models\SupplierDebt;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-overdue-supplier-debts')]
#[Description('Check for overdue supplier debts and notify pharmacy users')]
class CheckOverdueSupplierDebts extends Command
{
    public function handle(NotificationService $notifier)
    {
        $overdueDebts = SupplierDebt::where('due_date', '<', today())
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->with(['pharmacy.users', 'supplier'])
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
                    "Supplier {$debt->supplier->name} has passed the payment due date.",
                    ['supplier_debt_id' => $debt->id]
                );
            }
        }
    }
}
