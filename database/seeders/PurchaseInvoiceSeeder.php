<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseInvoiceService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        $supplier = Supplier::firstOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'name'        => 'MedPharma Distribution',
            ],
            [
                'phone'   => '+963112345678',
                'email'   => 'orders@medpharma.com',
                'address' => 'Industrial Zone, Damascus',
                'notes'   => 'Seeded supplier for purchase invoice testing',
            ]
        );

        $panadol   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panadol Extra')->first();
        $amoxil    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Amoxil 500mg')->first();
        $augmentin = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Augmentin 1g')->first();

        if (! $panadol || ! $amoxil || ! $augmentin) {
            $this->command->warn('Required products not found. Run ProductSeeder first.');

            return;
        }

        /** @var PurchaseInvoiceService $service */
        $service = app(PurchaseInvoiceService::class);

        // invoice 1    fully paid
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplier->id,
            //'invoice_date'   => now()->subDays(10)->toDateString(),
            'invoice_date'   => '2026-06-05',
            'payment_method' => 'cash',
            'amount_paid'    => 585000,
            'notes'          => 'Seeded — fully paid',
            'items'          => [
                [
                    'product_id'      => $panadol->id,
                    'quantity'        => 50,
                    'wholesale_price' => 4500,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-0001',
                    'expiry_date'     => now()->addYear()->toDateString(),
                    'selling_price'   => 5500,
                ],
                [
                    'product_id'      => $amoxil->id,
                    'quantity'        => 30,
                    'wholesale_price' => 12000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-0002',
                    'expiry_date'     => now()->addYears(2)->toDateString(),
                    'selling_price'   => 15000,
                ],
            ],
        ]);

        // invoice 2    partially paid, creates a supplier debt
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplier->id,
            //'invoice_date'   => now()->subDays(3)->toDateString(),
            'invoice_date'   => '2026-06-12',
            'payment_method' => 'debt',
            'amount_paid'    => 50000,
            'notes'          => 'Seeded — partial payment, remainder on debt',
            'items'          => [
                [
                    'product_id'      => $augmentin->id,
                    'quantity'        => 25,
                    'wholesale_price' => 45000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-0003',
                    'expiry_date'     => now()->addMonths(14)->toDateString(),
                    'selling_price'   => 52000,
                ],
            ],
        ]);

    }
}
