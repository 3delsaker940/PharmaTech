<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\CustomerReturnInvoiceService;
use Illuminate\Database\Seeder;

class CustomerReturnInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        $customer = Customer::where('pharmacy_id', $pharmacy->id)
            ->where('full_name', 'Hasan')
            ->first();

        if (! $customer) {
            $this->command->warn('Customer not found. Run CustomerSeeder first.');
            return;
        }

        // Get the original sales invoice linked to Hasan (partial payment one)
        $originalInvoice = SalesInvoice::where('pharmacy_id', $pharmacy->id)
            ->where('customer_id', $customer->id)
            ->first();

        $panadol = Product::where('pharmacy_id', $pharmacy->id)
            ->where('brand_name', 'Panadol Extra')
            ->first();

        if (! $panadol) {
            $this->command->warn('Required product not found. Run ProductSeeder first.');
            return;
        }

        /** @var CustomerReturnInvoiceService $service */
        $service = app(CustomerReturnInvoiceService::class);

        // Return 1 — linked to original sales invoice, cash refund
        $service->store($pharmacy, $owner, [
            'customer_id'               => $customer->id,
            'original_sales_invoice_id' => $originalInvoice?->id,
            'invoice_date'              => '2026-07-05',
            'refund_method'             => 'cash',
            'reason'                    => 'Product damaged',
            'notes'                     => 'Seeded — customer return with original invoice',
            'items'                     => [
                [
                    'product_id' => $panadol->id,
                    'quantity'   => 1,
                    'unit_price' => 5500,
                    'tax'        => 0,
                    'discount'   => 0,
                ],
            ],
        ]);

        // Return 2 — walk-in return, no customer, no original invoice, credit refund
        $service->store($pharmacy, $owner, [
            'customer_id'               => null,
            'original_sales_invoice_id' => null,
            'invoice_date'              => '2026-07-06',
            'refund_method'             => 'credit',
            'reason'                    => 'Wrong product dispensed',
            'notes'                     => 'Seeded — walk-in return, credit refund',
            'items'                     => [
                [
                    'product_id' => $panadol->id,
                    'quantity'   => 2,
                    'unit_price' => 5500,
                    'tax'        => 0,
                    'discount'   => 0,
                ],
            ],
        ]);
    }
}
