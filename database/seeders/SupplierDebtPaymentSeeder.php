<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\SupplierDebt;
use App\Models\User;
use App\Services\SupplierDebtService;
use Illuminate\Database\Seeder;

class SupplierDebtPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        $openDebts = SupplierDebt::where('pharmacy_id', $pharmacy->id)
            ->whereIn('status', ['open', 'partial'])
            ->get();

        if ($openDebts->isEmpty()) {
            $this->command->warn('No open supplier debts found. Run PurchaseInvoiceSeeder first.');
            return;
        }

        /** @var SupplierDebtService $service */
        $service = app(SupplierDebtService::class);

        foreach ($openDebts as $debt) {
            // Pay half the remaining amount for each open debt
            $paymentAmount = round($debt->remaining_amount / 2, 2);

            if ($paymentAmount < 0.01) {
                continue;
            }

            $service->recordPayment($debt, $owner, [
                'amount' => $paymentAmount,
                'payment_date' => '2026-07-10',
                'notes' => 'Seeded — partial supplier debt payment',
            ]);
        }
    }
}
