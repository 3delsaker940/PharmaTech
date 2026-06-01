<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockBatch;
use Carbon\Carbon;

class StockBatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            [
                'product_id' => 1, // Panadol
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-001',
                'expiry_date' => Carbon::now()->addYear()->format('Y-m-d'),
                'purchase_price' => 4500.00,
                'selling_price' => 5500.00,
                'quantity_on_hand' => 50,
                'received_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'product_id' => 1, // Panadol (دفعة ثانية)
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-002',
                'expiry_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'purchase_price' => 4000.00,
                'selling_price' => 5500.00,
                'quantity_on_hand' => 20,
                'received_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'product_id' => 2, // Amoxil
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-003',
                'expiry_date' => Carbon::now()->addYears(2)->format('Y-m-d'),
                'purchase_price' => 12000.00,
                'selling_price' => 15000.00,
                'quantity_on_hand' => 30,
                'received_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'product_id' => 3, // Centrum
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-004',
                'expiry_date' => Carbon::now()->addYears(3)->format('Y-m-d'),
                'purchase_price' => 85000.00,
                'selling_price' => 95000.00,
                'quantity_on_hand' => 15,
                'received_date' => Carbon::now()->subDays(20)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'product_id' => 4, // Concor
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-005',
                'expiry_date' => Carbon::now()->addMonths(18)->format('Y-m-d'),
                'purchase_price' => 18000.00,
                'selling_price' => 22000.00,
                'quantity_on_hand' => 40,
                'received_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'product_id' => 5, // Augmentin
                'pharmacy_id' => 1,
                'batch_number' => 'BCH-2026-006',
                'expiry_date' => Carbon::now()->addMonths(14)->format('Y-m-d'),
                'purchase_price' => 45000.00,
                'selling_price' => 52000.00,
                'quantity_on_hand' => 25,
                'received_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'status' => 'available',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];

        StockBatch::insert($batches);
    }
}
