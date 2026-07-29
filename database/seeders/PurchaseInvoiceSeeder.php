<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        // ── Two suppliers for Supplier Prices report variety ───────────────────
        $supplierA = Supplier::firstOrCreate(
            ['pharmacy_id' => $pharmacy->id, 'name' => 'MedPharma Distribution'],
            [
                'phone'   => '+963112345678',
                'email'   => 'orders@medpharma.com',
                'address' => 'Industrial Zone, Damascus',
                'notes'   => 'Primary wholesale distributor',
            ]
        );

        $supplierB = Supplier::firstOrCreate(
            ['pharmacy_id' => $pharmacy->id, 'name' => 'SyroDrug Wholesale'],
            [
                'phone'   => '+963199887766',
                'email'   => 'supply@syrodrug.sy',
                'address' => 'Mezzeh, Damascus',
                'notes'   => 'Secondary distributor — competitive pricing',
            ]
        );

        // ── Resolve products ───────────────────────────────────────────────────
        $panadol    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panadol Extra')->first();
        $amoxil     = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Amoxil 500mg')->first();
        $augmentin  = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Augmentin 1g')->first();
        $centrum    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Centrum Lutein')->first();
        $concor     = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Concor 5mg')->first();
        $omeprazole = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Omeprazole 20mg')->first();
        $betadine   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Betadine 500ml')->first();
        $crestor    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Crestor 10mg')->first();
        $glucophage = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Glucophage 1000mg')->first();

        if (! $panadol || ! $amoxil || ! $augmentin) {
            $this->command->warn('Core products not found. Run ProductSeeder first.');
            return;
        }

        /** @var PurchaseInvoiceService $service */
        $service = app(PurchaseInvoiceService::class);

        $today = Carbon::today();

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 1 — CURRENT STOCK (recent purchases, good expiry dates)
        // Stocks up the main selling products so reports have data to show.
        // ══════════════════════════════════════════════════════════════════════

        // Invoice A1 — supplierA, 12 days ago — core stock + normal expiry
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplierA->id,
            'invoice_date'   => $today->copy()->subDays(12)->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 1125000,
            'notes'          => 'Seeded — main stock replenishment',
            'items'          => [
                [
                    'product_id'      => $panadol->id,
                    'quantity'        => 300,
                    'wholesale_price' => 4500,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-001',
                    'expiry_date'     => $today->copy()->addYear()->toDateString(),
                    'selling_price'   => 5500,
                ],
                [
                    'product_id'      => $amoxil->id,
                    'quantity'        => 200,
                    'wholesale_price' => 12000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-002',
                    'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                    'selling_price'   => 15000,
                ],
                [
                    'product_id'      => $augmentin->id,
                    'quantity'        => 150,
                    'wholesale_price' => 45000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-003',
                    'expiry_date'     => $today->copy()->addMonths(18)->toDateString(),
                    'selling_price'   => 52000,
                ],
                [
                    'product_id'      => $omeprazole->id,
                    'quantity'        => 200,
                    'wholesale_price' => 8000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-004',
                    'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                    'selling_price'   => 13000,
                ],
                [
                    'product_id'      => $betadine->id,
                    'quantity'        => 120,
                    'wholesale_price' => 22000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-005',
                    'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                    'selling_price'   => 30000,
                ],
                [
                    'product_id'      => $centrum->id,
                    'quantity'        => 50,
                    'wholesale_price' => 85000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-006',
                    'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                    'selling_price'   => 95000,
                ],
                [
                    'product_id'      => $concor->id,
                    'quantity'        => 100,
                    'wholesale_price' => 18000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-007',
                    'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                    'selling_price'   => 22000,
                ],
            ],
        ]);

        // Invoice A2 — supplierA, 5 days ago — partial debt
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplierA->id,
            'invoice_date'   => $today->copy()->subDays(5)->toDateString(),
            'payment_method' => 'debt',
            'amount_paid'    => 100000,
            'notes'          => 'Seeded — partial payment, remainder on debt',
            'items'          => [
                [
                    'product_id'      => $augmentin->id,
                    'quantity'        => 15,
                    'wholesale_price' => 46000,   // slightly higher price than supplierA invoice
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-2026-008',
                    'expiry_date'     => $today->copy()->addMonths(14)->toDateString(),
                    'selling_price'   => 52000,
                ],
            ],
        ]);

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 2 — NEAR-EXPIRY BATCHES (Stock Health → Expiring Soon)
        // Purchased recently (within last 3 days) so FIFO keeps these batches
        // in stock after all the historic sales have run.
        // Expiry dates are set short so all threshold chips (7/30/60/90d) fire.
        // ══════════════════════════════════════════════════════════════════════

        // Panadol batch expiring in 7 days — critical, visible in all filters
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplierB->id,
            'invoice_date'   => $today->copy()->subDays(2)->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 135000,
            'notes'          => 'Seeded — near-expiry batch (7 days)',
            'items'          => [
                [
                    'product_id'      => $panadol->id,
                    'quantity'        => 30,
                    'wholesale_price' => 4500,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-EXP-001',
                    'expiry_date'     => $today->copy()->addDays(7)->toDateString(),
                    'selling_price'   => 5500,
                ],
            ],
        ]);

        // Amoxil batch expiring in 20 days — visible in 30d/60d/90d filters
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplierB->id,
            'invoice_date'   => $today->copy()->subDays(2)->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 240000,
            'notes'          => 'Seeded — near-expiry batch (20 days)',
            'items'          => [
                [
                    'product_id'      => $amoxil->id,
                    'quantity'        => 20,
                    'wholesale_price' => 12000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-EXP-002',
                    'expiry_date'     => $today->copy()->addDays(20)->toDateString(),
                    'selling_price'   => 15000,
                ],
            ],
        ]);

        // Omeprazole batch expiring in 45 days — visible in 60d/90d filters
        $service->store($pharmacy, $owner, [
            'supplier_id'    => $supplierA->id,
            'invoice_date'   => $today->copy()->subDays(1)->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 480000,
            'notes'          => 'Seeded — near-expiry batch (45 days)',
            'items'          => [
                [
                    'product_id'      => $omeprazole->id,
                    'quantity'        => 60,
                    'wholesale_price' => 8000,
                    'tax'             => 0,
                    'discount'        => 0,
                    'batch_number'    => 'BCH-EXP-003',
                    'expiry_date'     => $today->copy()->addDays(45)->toDateString(),
                    'selling_price'   => 13000,
                ],
            ],
        ]);

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 3 — LOW-STOCK PRODUCT
        // Glucophage: only 2 units in stock, min_stock=10 → shows as low-stock.
        // ══════════════════════════════════════════════════════════════════════
        if ($glucophage) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(20)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 30000,
                'notes'          => 'Seeded — intentionally tiny quantity for low-stock scenario',
                'items'          => [
                    [
                        'product_id'      => $glucophage->id,
                        'quantity'        => 2,    // well below min_stock of 10
                        'wholesale_price' => 15000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'BCH-LOW-001',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 20000,
                    ],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 4 — DEAD STOCK CANDIDATE
        // Crestor: purchased 9 months ago, never sold → no sales in last 90+ days.
        // ══════════════════════════════════════════════════════════════════════
        if ($crestor) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subMonths(9)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 650000,
                'notes'          => 'Seeded — old purchase, product never sold (dead stock)',
                'items'          => [
                    [
                        'product_id'      => $crestor->id,
                        'quantity'        => 10,
                        'wholesale_price' => 65000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'BCH-DEAD-001',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 78000,
                    ],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 5 — SUPPLIER PRICES HISTORY
        // Multiple purchases of the same products from BOTH suppliers at
        // different prices over 6 months → rich Supplier Prices report.
        // ══════════════════════════════════════════════════════════════════════

        // supplierA — Panadol price history (6 months, prices creeping up)
        $panadolPricesA = [4200, 4250, 4300, 4350, 4400, 4450];
        for ($i = 1; $i <= 6; $i++) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subMonths($i)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => $panadolPricesA[$i - 1] * 10,
                'notes'          => "Seeded — supplierA Panadol history month -{$i}",
                'items'          => [
                    [
                        'product_id'      => $panadol->id,
                        'quantity'        => 10,
                        'wholesale_price' => $panadolPricesA[$i - 1],
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => "BCH-PA-{$i}",
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 5500,
                    ],
                ],
            ]);
        }

        // supplierB — Panadol price history (consistently cheaper by ~5%)
        $panadolPricesB = [3990, 4000, 4050, 4100, 4150, 4200];
        for ($i = 1; $i <= 6; $i++) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subMonths($i)->subDays(10)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => $panadolPricesB[$i - 1] * 10,
                'notes'          => "Seeded — supplierB Panadol history month -{$i}",
                'items'          => [
                    [
                        'product_id'      => $panadol->id,
                        'quantity'        => 10,
                        'wholesale_price' => $panadolPricesB[$i - 1],
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => "BCH-PB-{$i}",
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 5500,
                    ],
                ],
            ]);
        }

        // supplierA — Augmentin price history (3 months)
        $augPrices = [44000, 44500, 45000];
        for ($i = 1; $i <= 3; $i++) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subMonths($i + 1)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => $augPrices[$i - 1] * 5,
                'notes'          => "Seeded — supplierA Augmentin history month -{$i}",
                'items'          => [
                    [
                        'product_id'      => $augmentin->id,
                        'quantity'        => 5,
                        'wholesale_price' => $augPrices[$i - 1],
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => "BCH-AA-{$i}",
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 52000,
                    ],
                ],
            ]);
        }

        // supplierB — Augmentin (slightly cheaper)
        $augPricesB = [43000, 43500];
        for ($i = 1; $i <= 2; $i++) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subMonths($i + 2)->subDays(5)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => $augPricesB[$i - 1] * 5,
                'notes'          => "Seeded — supplierB Augmentin history month -{$i}",
                'items'          => [
                    [
                        'product_id'      => $augmentin->id,
                        'quantity'        => 5,
                        'wholesale_price' => $augPricesB[$i - 1],
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => "BCH-AB-{$i}",
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 52000,
                    ],
                ],
            ]);
        }

        // supplierA — Omeprazole history (3 months)
        for ($i = 1; $i <= 3; $i++) {
            if ($omeprazole) {
                $service->store($pharmacy, $owner, [
                    'supplier_id'    => $supplierA->id,
                    'invoice_date'   => $today->copy()->subMonths($i + 1)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => (7500 + $i * 100) * 15,
                    'notes'          => "Seeded — supplierA Omeprazole history month -{$i}",
                    'items'          => [
                        [
                            'product_id'      => $omeprazole->id,
                            'quantity'        => 15,
                            'wholesale_price' => 7500 + ($i * 100),
                            'tax'             => 0,
                            'discount'        => 0,
                            'batch_number'    => "BCH-OA-{$i}",
                            'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                            'selling_price'   => 13000,
                        ],
                    ],
                ]);
            }
        }
    }
}
