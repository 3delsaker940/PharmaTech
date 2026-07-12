<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use App\Services\SalesInvoiceService;
use Illuminate\Database\Seeder;

class SalesInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }
        $customer1 = Customer::where('pharmacy_id', $pharmacy->id)
            ->where('full_name', 'Hasan')
            ->first();

        $customer2 = Customer::where('pharmacy_id', $pharmacy->id)
            ->where('full_name', 'joao')
            ->first();

        if (! $customer1 || ! $customer2) {
            $this->command->warn('Customers not found. Run CustomerSeeder first.');
            return;
        }

        $panadol= Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panadol Extra')->first();
        $amoxil = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Amoxil 500mg')->first();
        $augmentin = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Augmentin 1g')->first();

        if (! $panadol || ! $amoxil || ! $augmentin) {
            $this->command->warn('Required products not found. Run ProductSeeder first.');
            return;
        }

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        $service->store($pharmacy, $owner, [
            'customer_id'=> null,
            'invoice_date' => '2026-07-01',
            'payment_method' => 'cash',
            'amount_paid'=> 27500,
            'notes'=> 'Seeded — fully paid, walk-in customer',
            'items' => [
                [
                    'product_id'=> $panadol->id,
                    'quantity' => 5,
                    'selling_price' => 5500,
                    'tax' => 0,
                    'discount' => 0,
                ],
            ],
        ]);

        $service->store($pharmacy, $owner, [
            'customer_id' => $customer1->id,
            'invoice_date' => '2026-07-02',
            'payment_method'=> 'cash',
            'amount_paid' => 15000,
            'notes' => 'Seeded — partial payment, remainder on debt',
            'items'  => [
                [
                    'product_id'=> $amoxil->id,
                    'quantity' => 2,
                    'selling_price' => 15000,
                    'tax'  => 0,
                    'discount' => 0,
                ],
            ],
        ]);

        $service->store($pharmacy, $owner, [
            'customer_id'=> $customer2->id,
            'invoice_date'=> '2026-07-03',
            'payment_method' => 'debt',
            'amount_paid'=> 0,
            'notes' => 'Seeded — fully on debt',
            'items' => [
                [
                    'product_id' => $augmentin->id,
                    'quantity' => 3,
                    'selling_price' => 52000,
                    'tax' => 0,
                    'discount' => 0,
                ],
                [
                    'product_id' => $panadol->id,
                    'quantity' => 2,
                    'selling_price' => 5500,
                    'tax' => 0,
                    'discount'  => 0,
                ],
            ],
        ]);
    }
}
