<?php

namespace Database\Seeders;

use App\Models\CustomerDebt;
use App\Models\Pharmacy;
use App\Models\User;
use App\Services\CustomerDebtService;
use Illuminate\Database\Seeder;

class CustomerDebtPaymentSeeder extends Seeder
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

        $openDebts = CustomerDebt::where('pharmacy_id', $pharmacy->id)
            ->whereIn('status', ['open', 'partial'])
            ->get();

        if ($openDebts->isEmpty()) {
            $this->command->warn('No open customer debts found. Run SalesInvoiceSeeder first.');
            return;
        }

        /** @var CustomerDebtService $service */
        $service = app(CustomerDebtService::class);

        foreach ($openDebts as $debt) {
            // Pay half the remaining amount for each open debt
            $paymentAmount = round($debt->remaining_amount / 2, 2);

            if ($paymentAmount < 0.01) {
                continue;
            }

            $service->recordPayment($debt, $owner, [
                'amount' => $paymentAmount,
                'payment_date' => '2026-07-10',
                'notes' => 'Seeded — partial customer debt payment',
            ]);
        }
    }
}
