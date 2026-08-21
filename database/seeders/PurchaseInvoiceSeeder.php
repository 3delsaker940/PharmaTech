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

        // ══════════════════════════════════════════════════════════════════════
        // BLOCK 6 — IMPORTED DATASET PRODUCTS (barcodes 19-31)
        // Multiple batches per product across both suppliers, covering:
        // regular stock, near-expiry, low-stock, dead-stock, and price history.
        // ══════════════════════════════════════════════════════════════════════

        $antitussive = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Anti-Tussive Hama Pharma')->first();
        $unadol      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Unadol FORTE')->first();
        $spasmaver   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Spasmaver')->first();
        $brufen      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Uni Brufen 400mg')->first();
        $brandexP    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Brandex-P')->first();
        $losartic    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Losartic')->first();
        $panthenol   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panthenol Rama')->first();
        $fucine      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Fucine Dermic Ointment')->first();
        $toularynx   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Toularynx Thym')->first();
        $bonacare    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'New Bonacare')->first();
        $osteodam    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Osteodam Plus K2')->first();
        $noflu       = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'NoFlu Day And Night')->first();
        $oneAsian    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'One-Asian 1')->first();

        // ── 19: Anti-Tussive — main stock (supplierA) + top-up (supplierB) ─────
        if ($antitussive) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(25)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 6500 * 80,
                'notes'          => 'Seeded — Anti-Tussive main stock',
                'items'          => [
                    [
                        'product_id'      => $antitussive->id,
                        'quantity'        => 80,
                        'wholesale_price' => 6500,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'TU-35',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 9000,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(6)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 6650 * 40,
                'notes'          => 'Seeded — Anti-Tussive restock, second batch',
                'items'          => [
                    [
                        'product_id'      => $antitussive->id,
                        'quantity'        => 40,
                        'wholesale_price' => 6650,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'TU-41',
                        'expiry_date'     => $today->copy()->addYears(2)->addMonths(6)->toDateString(),
                        'selling_price'   => 9000,
                    ],
                ],
            ]);
        }

        // ── 20: Unadol FORTE — fresh main batch + a near-expiry top-up batch ───
        if ($unadol) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(18)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 3500 * 150,
                'notes'          => 'Seeded — Unadol FORTE main stock',
                'items'          => [
                    [
                        'product_id'      => $unadol->id,
                        'quantity'        => 150,
                        'wholesale_price' => 3500,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '26-UNFT-2',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 5000,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(3)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 3600 * 25,
                'notes'          => 'Seeded — Unadol FORTE near-expiry top-up batch',
                'items'          => [
                    [
                        'product_id'      => $unadol->id,
                        'quantity'        => 25,
                        'wholesale_price' => 3600,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '26-UNFT-5',
                        'expiry_date'     => $today->copy()->addDays(35)->toDateString(),
                        'selling_price'   => 5000,
                    ],
                ],
            ]);
        }

        // ── 21: Spasmaver — dead stock: old purchase, never sold ───────────────
        if ($spasmaver) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subMonths(8)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 9000 * 20,
                'notes'          => 'Seeded — Spasmaver old purchase, never sold (dead stock)',
                'items'          => [
                    [
                        'product_id'      => $spasmaver->id,
                        'quantity'        => 20,
                        'wholesale_price' => 9000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'T-8420',
                        'expiry_date'     => $today->copy()->addMonths(4)->toDateString(),
                        'selling_price'   => 12500,
                    ],
                ],
            ]);
        }

        // ── 22: Uni Brufen — high-volume regular stock across 2 batches ────────
        if ($brufen) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(15)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 5000 * 250,
                'notes'          => 'Seeded — Uni Brufen main stock',
                'items'          => [
                    [
                        'product_id'      => $brufen->id,
                        'quantity'        => 250,
                        'wholesale_price' => 5000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '25-UB4T-2',
                        'expiry_date'     => $today->copy()->addYears(2)->addMonths(6)->toDateString(),
                        'selling_price'   => 7000,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(4)->toDateString(),
                'payment_method' => 'debt',
                'amount_paid'    => 4900 * 50,
                'notes'          => 'Seeded — Uni Brufen restock, partial debt',
                'items'          => [
                    [
                        'product_id'      => $brufen->id,
                        'quantity'        => 100,
                        'wholesale_price' => 4900,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '25-UB4T-6',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 7000,
                    ],
                ],
            ]);
        }

        // ── 23: Brandex-P — low-stock scenario (tiny quantity, min_stock=10) ───
        if ($brandexP) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(22)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 11000 * 3,
                'notes'          => 'Seeded — Brandex-P intentionally tiny quantity for low-stock scenario',
                'items'          => [
                    [
                        'product_id'      => $brandexP->id,
                        'quantity'        => 3,
                        'wholesale_price' => 11000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '6115',
                        'expiry_date'     => $today->copy()->addMonths(10)->toDateString(),
                        'selling_price'   => 15500,
                    ],
                ],
            ]);
        }

        // ── 24: Losartic — regular stock + supplier price history (2 batches) ──
        if ($losartic) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(10)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 16000 * 90,
                'notes'          => 'Seeded — Losartic main stock',
                'items'          => [
                    [
                        'product_id'      => $losartic->id,
                        'quantity'        => 90,
                        'wholesale_price' => 16000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'T-8974',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 21000,
                    ],
                ],
            ]);

            $losarticPricesA = [15500, 15700, 15900];
            for ($i = 1; $i <= 3; $i++) {
                $service->store($pharmacy, $owner, [
                    'supplier_id'    => $supplierA->id,
                    'invoice_date'   => $today->copy()->subMonths($i + 1)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => $losarticPricesA[$i - 1] * 10,
                    'notes'          => "Seeded — supplierA Losartic history month -{$i}",
                    'items'          => [
                        [
                            'product_id'      => $losartic->id,
                            'quantity'        => 10,
                            'wholesale_price' => $losarticPricesA[$i - 1],
                            'tax'             => 0,
                            'discount'        => 0,
                            'batch_number'    => "T-LA-{$i}",
                            'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                            'selling_price'   => 21000,
                        ],
                    ],
                ]);
            }

            $losarticPricesB = [15200, 15300];
            for ($i = 1; $i <= 2; $i++) {
                $service->store($pharmacy, $owner, [
                    'supplier_id'    => $supplierB->id,
                    'invoice_date'   => $today->copy()->subMonths($i + 2)->subDays(6)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => $losarticPricesB[$i - 1] * 10,
                    'notes'          => "Seeded — supplierB Losartic history month -{$i}",
                    'items'          => [
                        [
                            'product_id'      => $losartic->id,
                            'quantity'        => 10,
                            'wholesale_price' => $losarticPricesB[$i - 1],
                            'tax'             => 0,
                            'discount'        => 0,
                            'batch_number'    => "T-LB-{$i}",
                            'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                            'selling_price'   => 21000,
                        ],
                    ],
                ]);
            }
        }

        // ── 25: Panthenol Rama — near-expiry (critical, ~15 days) + older batch ─
        if ($panthenol) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(30)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 4500 * 40,
                'notes'          => 'Seeded — Panthenol Rama earlier batch',
                'items'          => [
                    [
                        'product_id'      => $panthenol->id,
                        'quantity'        => 40,
                        'wholesale_price' => 4500,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '049',
                        'expiry_date'     => $today->copy()->addMonths(8)->toDateString(),
                        'selling_price'   => 6500,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(3)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 4600 * 15,
                'notes'          => 'Seeded — Panthenol Rama near-expiry batch (15 days)',
                'items'          => [
                    [
                        'product_id'      => $panthenol->id,
                        'quantity'        => 15,
                        'wholesale_price' => 4600,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '055',
                        'expiry_date'     => $today->copy()->addDays(15)->toDateString(),
                        'selling_price'   => 6500,
                    ],
                ],
            ]);
        }

        // ── 26: Fucine — regular stock, two batches ─────────────────────────────
        if ($fucine) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(14)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 9500 * 60,
                'notes'          => 'Seeded — Fucine Dermic Ointment main stock',
                'items'          => [
                    [
                        'product_id'      => $fucine->id,
                        'quantity'        => 60,
                        'wholesale_price' => 9500,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '41229',
                        'expiry_date'     => $today->copy()->addYears(2)->addMonths(8)->toDateString(),
                        'selling_price'   => 13500,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(5)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 9700 * 20,
                'notes'          => 'Seeded — Fucine restock',
                'items'          => [
                    [
                        'product_id'      => $fucine->id,
                        'quantity'        => 20,
                        'wholesale_price' => 9700,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '41305',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 13500,
                    ],
                ],
            ]);
        }

        // ── 27: Toularynx Thym — critical near-expiry (7 days) + fresh batch ────
        if ($toularynx) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(1)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 4000 * 12,
                'notes'          => 'Seeded — Toularynx Thym critical near-expiry batch (7 days)',
                'items'          => [
                    [
                        'product_id'      => $toularynx->id,
                        'quantity'        => 12,
                        'wholesale_price' => 4000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '54231',
                        'expiry_date'     => $today->copy()->addDays(7)->toDateString(),
                        'selling_price'   => 6000,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(20)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 4100 * 30,
                'notes'          => 'Seeded — Toularynx Thym fresh restock batch',
                'items'          => [
                    [
                        'product_id'      => $toularynx->id,
                        'quantity'        => 30,
                        'wholesale_price' => 4100,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '54290',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 6000,
                    ],
                ],
            ]);
        }

        // ── 28: New Bonacare — regular stock ────────────────────────────────────
        if ($bonacare) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(9)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 14000 * 70,
                'notes'          => 'Seeded — New Bonacare main stock',
                'items'          => [
                    [
                        'product_id'      => $bonacare->id,
                        'quantity'        => 70,
                        'wholesale_price' => 14000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'F9P',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 19000,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(2)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 14200 * 25,
                'notes'          => 'Seeded — New Bonacare restock',
                'items'          => [
                    [
                        'product_id'      => $bonacare->id,
                        'quantity'        => 25,
                        'wholesale_price' => 14200,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => 'F9T',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 19000,
                    ],
                ],
            ]);
        }

        // ── 29: Osteodam Plus K2 — main stock + rich supplier price history ────
        if ($osteodam) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(11)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 17000 * 60,
                'notes'          => 'Seeded — Osteodam Plus K2 main stock',
                'items'          => [
                    [
                        'product_id'      => $osteodam->id,
                        'quantity'        => 60,
                        'wholesale_price' => 17000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '9509',
                        'expiry_date'     => $today->copy()->addYears(3)->toDateString(),
                        'selling_price'   => 23000,
                    ],
                ],
            ]);

            $osteodamPricesA = [16500, 16700, 16900, 17100];
            for ($i = 1; $i <= 4; $i++) {
                $service->store($pharmacy, $owner, [
                    'supplier_id'    => $supplierA->id,
                    'invoice_date'   => $today->copy()->subMonths($i)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => $osteodamPricesA[$i - 1] * 8,
                    'notes'          => "Seeded — supplierA Osteodam history month -{$i}",
                    'items'          => [
                        [
                            'product_id'      => $osteodam->id,
                            'quantity'        => 8,
                            'wholesale_price' => $osteodamPricesA[$i - 1],
                            'tax'             => 0,
                            'discount'        => 0,
                            'batch_number'    => "9509-A{$i}",
                            'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                            'selling_price'   => 23000,
                        ],
                    ],
                ]);
            }

            $osteodamPricesB = [16200, 16350, 16500];
            for ($i = 1; $i <= 3; $i++) {
                $service->store($pharmacy, $owner, [
                    'supplier_id'    => $supplierB->id,
                    'invoice_date'   => $today->copy()->subMonths($i)->subDays(12)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => $osteodamPricesB[$i - 1] * 8,
                    'notes'          => "Seeded — supplierB Osteodam history month -{$i}",
                    'items'          => [
                        [
                            'product_id'      => $osteodam->id,
                            'quantity'        => 8,
                            'wholesale_price' => $osteodamPricesB[$i - 1],
                            'tax'             => 0,
                            'discount'        => 0,
                            'batch_number'    => "9509-B{$i}",
                            'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                            'selling_price'   => 23000,
                        ],
                    ],
                ]);
            }
        }

        // ── 30: NoFlu Day And Night — seasonal restock, two batches ─────────────
        if ($noflu) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(17)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 7500 * 90,
                'notes'          => 'Seeded — NoFlu Day And Night main stock',
                'items'          => [
                    [
                        'product_id'      => $noflu->id,
                        'quantity'        => 90,
                        'wholesale_price' => 7500,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '148',
                        'expiry_date'     => $today->copy()->addYears(2)->addMonths(6)->toDateString(),
                        'selling_price'   => 10500,
                    ],
                ],
            ]);

            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierB->id,
                'invoice_date'   => $today->copy()->subDays(1)->toDateString(),
                'payment_method' => 'debt',
                'amount_paid'    => 0,
                'notes'          => 'Seeded — NoFlu restock, on debt',
                'items'          => [
                    [
                        'product_id'      => $noflu->id,
                        'quantity'        => 40,
                        'wholesale_price' => 7600,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '162',
                        'expiry_date'     => $today->copy()->addYears(2)->toDateString(),
                        'selling_price'   => 10500,
                    ],
                ],
            ]);
        }

        // ── 31: One-Asian 1 — critical low-stock scenario ───────────────────────
        if ($oneAsian) {
            $service->store($pharmacy, $owner, [
                'supplier_id'    => $supplierA->id,
                'invoice_date'   => $today->copy()->subDays(28)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 13000 * 3,
                'notes'          => 'Seeded — One-Asian 1 intentionally tiny quantity for low-stock scenario',
                'items'          => [
                    [
                        'product_id'      => $oneAsian->id,
                        'quantity'        => 3,
                        'wholesale_price' => 13000,
                        'tax'             => 0,
                        'discount'        => 0,
                        'batch_number'    => '64010',
                        'expiry_date'     => $today->copy()->addYears(1)->toDateString(),
                        'selling_price'   => 18000,
                    ],
                ],
            ]);
        }
    }
}
