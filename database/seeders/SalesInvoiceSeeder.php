<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use App\Services\SalesInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalesInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        $customer1 = Customer::where('pharmacy_id', $pharmacy->id)->where('full_name', 'Hasan')->first();
        $customer2 = Customer::where('pharmacy_id', $pharmacy->id)->where('full_name', 'joao')->first();
        $customer3 = Customer::where('pharmacy_id', $pharmacy->id)->where('full_name', 'omid')->first();
        $customer4 = Customer::where('pharmacy_id', $pharmacy->id)->where('full_name', 'Layla')->first();
        $customer5 = Customer::where('pharmacy_id', $pharmacy->id)->where('full_name', 'Karim')->first();
        // NOTE: 'Sara' is intentionally left with no sales invoices — a brand-new
        // customer with no purchase history yet.

        if (! $customer1 || ! $customer2) {
            $this->command->warn('Customers not found. Run CustomerSeeder first.');
            return;
        }

        // ── Resolve all products ───────────────────────────────────────────────
        $panadol    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panadol Extra')->first();
        $amoxil     = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Amoxil 500mg')->first();
        $augmentin  = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Augmentin 1g')->first();
        $centrum    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Centrum Lutein')->first();
        $concor     = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Concor 5mg')->first();
        $omeprazole = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Omeprazole 20mg')->first();
        $betadine   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Betadine 500ml')->first();

        if (! $panadol || ! $amoxil || ! $augmentin) {
            $this->command->warn('Core products not found. Run ProductSeeder first.');
            return;
        }

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        $today = Carbon::today();

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 1 — TODAY & YESTERDAY
        // Fresh daily data so the "Daily" period filter always shows something.
        // ══════════════════════════════════════════════════════════════════════

        $service->store($pharmacy, $owner, [
            'customer_id'    => null,
            'invoice_date'   => $today->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 27500,
            'notes'          => 'Seeded — walk-in, today',
            'items'          => [
                ['product_id' => $panadol->id, 'quantity' => 5, 'selling_price' => 5500, 'tax' => 0, 'discount' => 0],
            ],
        ]);

        $service->store($pharmacy, $owner, [
            'customer_id'    => $customer2->id,
            'invoice_date'   => $today->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 104000,
            'notes'          => 'Seeded — customer sale, today',
            'items'          => [
                ['product_id' => $augmentin->id, 'quantity' => 2, 'selling_price' => 52000, 'tax' => 0, 'discount' => 0],
            ],
        ]);

        if ($omeprazole) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer1->id,
                'invoice_date'   => $today->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 39000,
                'notes'          => 'Seeded — Omeprazole sale, today',
                'items'          => [
                    ['product_id' => $omeprazole->id, 'quantity' => 3, 'selling_price' => 13000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        $service->store($pharmacy, $owner, [
            'customer_id'    => $customer1->id,
            'invoice_date'   => $today->copy()->subDay()->toDateString(),
            'payment_method' => 'cash',
            'amount_paid'    => 30000,
            'notes'          => 'Seeded — yesterday',
            'items'          => [
                ['product_id' => $amoxil->id, 'quantity' => 2, 'selling_price' => 15000, 'tax' => 0, 'discount' => 0],
            ],
        ]);

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 2 — LAST 30 DAYS (daily sales, multiple products)
        // Gives the Sales daily chart a full 30-day bar chart.
        // Each day sells a mix of products with realistic variation.
        // ══════════════════════════════════════════════════════════════════════

        // Sales volume pattern: weekends slightly higher
        $dailyItems = [
            // [product, qty, price]  — rotated by day index
            [$panadol,   8,  5500],
            [$amoxil,    3,  15000],
            [$augmentin, 2,  52000],
            [$omeprazole, 5, 13000],
            [$panadol,   6,  5500],
            [$amoxil,    4,  15000],
            [$betadine,  2,  30000],
        ];

        for ($i = 2; $i <= 30; $i++) {
            $date    = $today->copy()->subDays($i);
            $pattern = $dailyItems[$i % count($dailyItems)];
            [$prod, $qty, $price] = $pattern;

            if (! $prod) continue;

            // Vary quantity slightly to make the chart look natural
            $varQty = max(1, $qty + ($i % 3) - 1);

            $service->store($pharmacy, $owner, [
                'customer_id'    => ($i % 6 === 0)
                    ? (($i % 4 === 0) ? $customer1->id : $customer2->id)  // debt always needs a customer
                    : (($i % 4 === 0) ? $customer1->id : (($i % 7 === 0) ? $customer2->id : null)),
                'invoice_date'   => $date->toDateString(),
                'payment_method' => ($i % 6 === 0) ? 'debt' : 'cash',
                'amount_paid'    => ($i % 6 === 0) ? 0 : ($varQty * $price),
                'notes'          => "Seeded — daily history day -{$i}",
                'items'          => [
                    ['product_id' => $prod->id, 'quantity' => $varQty, 'selling_price' => $price, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // A couple of multi-item invoices to simulate real days
        $service->store($pharmacy, $owner, [
            'customer_id'    => $customer2->id,
            'invoice_date'   => $today->copy()->subDays(4)->toDateString(),
            'payment_method' => 'debt',
            'amount_paid'    => 0,
            'notes'          => 'Seeded — multi-item debt invoice',
            'items'          => [
                ['product_id' => $augmentin->id, 'quantity' => 3, 'selling_price' => 52000, 'tax' => 0, 'discount' => 0],
                ['product_id' => $panadol->id,   'quantity' => 2, 'selling_price' => 5500,  'tax' => 0, 'discount' => 0],
            ],
        ]);

        if ($centrum) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer3?->id ?? $customer1->id,
                'invoice_date'   => $today->copy()->subDays(10)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 190000,
                'notes'          => 'Seeded — Centrum sale',
                'items'          => [
                    ['product_id' => $centrum->id, 'quantity' => 2, 'selling_price' => 95000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($concor) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer1->id,
                'invoice_date'   => $today->copy()->subDays(15)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 66000,
                'notes'          => 'Seeded — Concor sale',
                'items'          => [
                    ['product_id' => $concor->id, 'quantity' => 3, 'selling_price' => 22000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 3 — MONTHS 2-6 AGO (weekly cadence)
        // Covers the "Weekly" and "Monthly" period views in the Sales report.
        // One invoice every 3 days, cycling through all products.
        // ══════════════════════════════════════════════════════════════════════

        $historicProducts = array_filter([
            [$panadol,    8,  5500,  'Panadol bulk'],
            [$amoxil,     5,  15000, 'Amoxil bulk'],
            [$augmentin,  3,  52000, 'Augmentin bulk'],
            $omeprazole ? [$omeprazole, 6, 13000, 'Omeprazole bulk'] : null,
            $centrum    ? [$centrum,    2, 95000, 'Centrum bulk']    : null,
            $betadine   ? [$betadine,   3, 30000, 'Betadine bulk']   : null,
            $concor     ? [$concor,     4, 22000, 'Concor bulk']     : null,
        ]);
        $historicProducts = array_values($historicProducts);
        $pCount = count($historicProducts);

        // Day-31 through day-180 → one sale every 3 days
        for ($day = 31; $day <= 180; $day += 3) {
            $date    = $today->copy()->subDays($day);
            $pidx    = (int) ($day / 3) % $pCount;
            [$prod, $qty, $price, $note] = $historicProducts[$pidx];

            // Seasonal variation — higher volume in months 2-3
            $multiplier = ($day <= 90) ? 2 : 1;
            $varQty     = $qty * $multiplier + ($day % 2);

            $customers  = [null, $customer1->id, $customer2->id, null, $customer1->id];
            $custId     = $customers[$day % count($customers)];
            $isPaid     = ($day % 9 !== 0);

            // Debt invoices must always have a customer — fall back to customer1.
            if (! $isPaid && $custId === null) {
                $custId = $customer1->id;
            }

            $service->store($pharmacy, $owner, [
                'customer_id'    => $custId,
                'invoice_date'   => $date->toDateString(),
                'payment_method' => $isPaid ? 'cash' : 'debt',
                'amount_paid'    => $isPaid ? ($varQty * $price) : 0,
                'notes'          => "Seeded — {$note} (day -{$day})",
                'items'          => [
                    ['product_id' => $prod->id, 'quantity' => $varQty, 'selling_price' => $price, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 4 — IMPORTED DATASET PRODUCTS (barcodes 19-31)
        // Regular sales, seasonal spikes, and a couple of near-zero sellers
        // (Spasmaver, One-Asian 1) to match their dead-stock / low-stock batches.
        // ══════════════════════════════════════════════════════════════════════

        $antitussive = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Anti-Tussive Hama Pharma')->first();
        $unadol      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Unadol FORTE')->first();
        $brufen      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Uni Brufen 400mg')->first();
        $brandexP    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Brandex-P')->first();
        $losartic    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Losartic')->first();
        $panthenol   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Panthenol Rama')->first();
        $fucine      = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Fucine Dermic Ointment')->first();
        $toularynx   = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Toularynx Thym')->first();
        $bonacare    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'New Bonacare')->first();
        $osteodam    = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'Osteodam Plus K2')->first();
        $noflu       = Product::where('pharmacy_id', $pharmacy->id)->where('brand_name', 'NoFlu Day And Night')->first();

        // Today / yesterday quick sales — keeps "Daily" filter populated
        if ($unadol) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => null,
                'invoice_date'   => $today->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 5000 * 4,
                'notes'          => 'Seeded — Unadol FORTE walk-in, today',
                'items'          => [
                    ['product_id' => $unadol->id, 'quantity' => 4, 'selling_price' => 5000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($brufen) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer2->id,
                'invoice_date'   => $today->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 7000 * 6,
                'notes'          => 'Seeded — Uni Brufen sale, today',
                'items'          => [
                    ['product_id' => $brufen->id, 'quantity' => 6, 'selling_price' => 7000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($noflu) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer1->id,
                'invoice_date'   => $today->copy()->subDay()->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 10500 * 2,
                'notes'          => 'Seeded — NoFlu sale, yesterday',
                'items'          => [
                    ['product_id' => $noflu->id, 'quantity' => 2, 'selling_price' => 10500, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // Daily rotation across the last 25 days for the steady sellers
        $newDailyItems = array_values(array_filter([
            $antitussive ? [$antitussive, 3, 9000]  : null,
            $brufen      ? [$brufen,      6, 7000]  : null,
            $unadol      ? [$unadol,      5, 5000]  : null,
            $fucine      ? [$fucine,      2, 13500] : null,
            $losartic    ? [$losartic,    3, 21000] : null,
        ]));

        if (count($newDailyItems) > 0) {
            $nCount = count($newDailyItems);
            for ($i = 1; $i <= 25; $i++) {
                $date    = $today->copy()->subDays($i);
                $pattern = $newDailyItems[$i % $nCount];
                [$prod, $qty, $price] = $pattern;

                $varQty = max(1, $qty + ($i % 3) - 1);

                $service->store($pharmacy, $owner, [
                    'customer_id'    => ($i % 5 === 0) ? $customer1->id : (($i % 8 === 0) ? $customer2->id : null),
                    'invoice_date'   => $date->toDateString(),
                    'payment_method' => ($i % 8 === 0) ? 'debt' : 'cash',
                    'amount_paid'    => ($i % 8 === 0) ? 0 : ($varQty * $price),
                    'notes'          => "Seeded — imported-dataset daily history day -{$i}",
                    'items'          => [
                        ['product_id' => $prod->id, 'quantity' => $varQty, 'selling_price' => $price, 'tax' => 0, 'discount' => 0],
                    ],
                ]);
            }
        }

        // Brandex-P — a couple of small sales (batch was deliberately tiny → low stock)
        if ($brandexP) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer1->id,
                'invoice_date'   => $today->copy()->subDays(8)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 15500,
                'notes'          => 'Seeded — Brandex-P sale',
                'items'          => [
                    ['product_id' => $brandexP->id, 'quantity' => 1, 'selling_price' => 15500, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // Panthenol Rama — moderate sales
        if ($panthenol) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer2->id,
                'invoice_date'   => $today->copy()->subDays(6)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 6500 * 2,
                'notes'          => 'Seeded — Panthenol Rama sale',
                'items'          => [
                    ['product_id' => $panthenol->id, 'quantity' => 2, 'selling_price' => 6500, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // Toularynx Thym — a couple of sales chipping at the near-expiry batch
        if ($toularynx) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer3?->id ?? $customer1->id,
                'invoice_date'   => $today->copy()->subDays(2)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 6000 * 3,
                'notes'          => 'Seeded — Toularynx Thym sale',
                'items'          => [
                    ['product_id' => $toularynx->id, 'quantity' => 3, 'selling_price' => 6000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        // New Bonacare & Osteodam — vitamins, monthly-ish sales pattern (months 1-6)
        for ($m = 1; $m <= 6; $m++) {
            if ($bonacare) {
                $service->store($pharmacy, $owner, [
                    'customer_id'    => ($m % 2 === 0) ? $customer1->id : $customer2->id,
                    'invoice_date'   => $today->copy()->subMonths($m)->subDays(3)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => 19000 * 2,
                    'notes'          => "Seeded — New Bonacare bulk, month -{$m}",
                    'items'          => [
                        ['product_id' => $bonacare->id, 'quantity' => 2, 'selling_price' => 19000, 'tax' => 0, 'discount' => 0],
                    ],
                ]);
            }

            if ($osteodam) {
                $service->store($pharmacy, $owner, [
                    'customer_id'    => ($m % 2 === 0) ? $customer2->id : $customer1->id,
                    'invoice_date'   => $today->copy()->subMonths($m)->subDays(8)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => 23000,
                    'notes'          => "Seeded — Osteodam Plus K2 sale, month -{$m}",
                    'items'          => [
                        ['product_id' => $osteodam->id, 'quantity' => 1, 'selling_price' => 23000, 'tax' => 0, 'discount' => 0],
                    ],
                ]);
            }
        }

        // NoFlu Day And Night — seasonal spike in the last 30 days (cold season)
        if ($noflu) {
            for ($i = 3; $i <= 28; $i += 4) {
                $service->store($pharmacy, $owner, [
                    'customer_id'    => ($i % 12 === 0) ? $customer1->id : null,
                    'invoice_date'   => $today->copy()->subDays($i)->toDateString(),
                    'payment_method' => 'cash',
                    'amount_paid'    => 10500 * 3,
                    'notes'          => "Seeded — NoFlu seasonal sale, day -{$i}",
                    'items'          => [
                        ['product_id' => $noflu->id, 'quantity' => 3, 'selling_price' => 10500, 'tax' => 0, 'discount' => 0],
                    ],
                ]);
            }
        }

        // NOTE: Spasmaver and One-Asian 1 intentionally have NO sales records here
        // — Spasmaver stays a dead-stock candidate, One-Asian 1 stays a
        // critical low-stock item (matches their PurchaseInvoiceSeeder scenarios).

        // ══════════════════════════════════════════════════════════════════════
        // SECTION 5 — NEW CUSTOMERS (Layla, Karim)
        // Layla: monthly vitamin buyer. Karim: occasional mixed purchases.
        // 'Sara' is intentionally left with no invoices — brand-new customer.
        // ══════════════════════════════════════════════════════════════════════

        if ($customer4 && $bonacare) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer4->id,
                'invoice_date'   => $today->copy()->subDays(5)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 19000,
                'notes'          => 'Seeded — Layla, monthly vitamin purchase',
                'items'          => [
                    ['product_id' => $bonacare->id, 'quantity' => 1, 'selling_price' => 19000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($customer4 && $osteodam) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer4->id,
                'invoice_date'   => $today->copy()->subDays(35)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => 23000,
                'notes'          => 'Seeded — Layla, previous month vitamin purchase',
                'items'          => [
                    ['product_id' => $osteodam->id, 'quantity' => 1, 'selling_price' => 23000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($customer5 && $panadol && $brufen) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer5->id,
                'invoice_date'   => $today->copy()->subDays(7)->toDateString(),
                'payment_method' => 'cash',
                'amount_paid'    => (5500 * 2) + 7000,
                'notes'          => 'Seeded — Karim, mixed purchase',
                'items'          => [
                    ['product_id' => $panadol->id, 'quantity' => 2, 'selling_price' => 5500, 'tax' => 0, 'discount' => 0],
                    ['product_id' => $brufen->id,  'quantity' => 1, 'selling_price' => 7000, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }

        if ($customer5 && $noflu) {
            $service->store($pharmacy, $owner, [
                'customer_id'    => $customer5->id,
                'invoice_date'   => $today->copy()->subDays(1)->toDateString(),
                'payment_method' => 'debt',
                'amount_paid'    => 0,
                'notes'          => 'Seeded — Karim, on debt',
                'items'          => [
                    ['product_id' => $noflu->id, 'quantity' => 1, 'selling_price' => 10500, 'tax' => 0, 'discount' => 0],
                ],
            ]);
        }
    }
}
