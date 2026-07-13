<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSalesReport(Pharmacy $pharmacy, array $filters): array
    {
        $pharmacyId = $pharmacy->id;
        $period = $filters['period'] ?? 'daily';
        $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
        $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
        $groupFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        $summary = SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereBetween('invoice_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('
                COUNT(*) as total_invoices,
                COALESCE(SUM(grand_total), 0) as total_revenue,
                COALESCE(SUM(discount_total), 0) as total_discount,
                COALESCE(SUM(tax_total), 0) as total_tax,
                COALESCE(SUM(amount_paid), 0) as total_collected,
                COALESCE(SUM(amount_due), 0) as total_outstanding,
                COALESCE(SUM(grand_total) / NULLIF(COUNT(*), 0), 0) as avg_invoice_value
            ')
            ->first();

        $unitsSold = (int) SalesInvoiceItem::whereHas('salesInvoice', fn ($q) =>
        $q->where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereBetween('invoice_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
        )->sum('quantity');

        $breakdown = SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereBetween('invoice_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw("
                DATE_FORMAT(invoice_date, '{$groupFormat}') as period_label,
                COUNT(*) as invoice_count,
                COALESCE(SUM(grand_total), 0) as revenue,
                COALESCE(SUM(discount_total), 0) as discount,
                COALESCE(SUM(tax_total), 0) as tax,
                COALESCE(SUM(amount_paid), 0) as collected,
                COALESCE(SUM(amount_due), 0) as outstanding
            ")
            ->groupByRaw("DATE_FORMAT(invoice_date, '{$groupFormat}')")
            ->orderBy('period_label')
            ->get();
        $filledBreakdown = $this->fillMissingPeriods(
            $breakdown->keyBy('period_label'),
            $dateFrom,
            $dateTo,
            $period,
            $groupFormat
        );
        return [
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'summary' => [
                'total_invoices' => (int) $summary->total_invoices,
                'total_revenue' => (float) $summary->total_revenue,
                'total_discount' => (float) $summary->total_discount,
                'total_tax' => (float) $summary->total_tax,
                'total_collected' => (float) $summary->total_collected,
                'total_outstanding' => (float) $summary->total_outstanding,
                'avg_invoice_value' => round((float) $summary->avg_invoice_value, 2),
                'units_sold' => $unitsSold,
            ],

            'breakdown' => $filledBreakdown,
        ];
    }
    public function getTopProducts(Pharmacy $pharmacy, array $filters): array
    {
        $pharmacyId = $pharmacy->id;
        $dateFrom = Carbon::parse($filters['date_from'])->toDateString();
        $dateTo = Carbon::parse($filters['date_to'])->toDateString();
        $limit = min((int) ($filters['limit'] ?? 10), 50);
        $rows = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('stock_movements.pharmacy_id', $pharmacyId)
            ->where('stock_movements.movement_type', 'sale_out')
            ->whereBetween(DB::raw('DATE(stock_movements.created_at)'), [$dateFrom, $dateTo])
            ->groupBy(
                'products.id',
                'products.brand_name',
                'products.ar_name',
                'products.selling_price',
                'products.buying_price',
                'categories.name'
            )
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                products.selling_price,
                products.buying_price,
                categories.name as category,
                SUM(ABS(stock_movements.quantity_change)) as total_units_sold,
                SUM(ABS(stock_movements.quantity_change)) * products.selling_price as total_revenue
            ')
            ->orderByDesc('total_units_sold')
            ->limit($limit)
            ->get();
        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
            'products' => $rows->map(fn ($row, $index) => [
                'rank' => $index + 1,
                'product_id' => $row->product_id,
                'brand_name' => $row->brand_name,
                'ar_name' => $row->ar_name,
                'category' => $row->category,
                'selling_price' => (float) $row->selling_price,
                'buying_price' => (float) $row->buying_price,
                'total_units_sold' => (int)   $row->total_units_sold,
                'total_revenue' => round((float) $row->total_revenue, 2),
            ])->values()->all(),
        ];
    }
    public function getProfitReport(Pharmacy $pharmacy, array $filters): array
    {
        $pharmacyId = $pharmacy->id;
        $dateFrom = Carbon::parse($filters['date_from'])->toDateString();
        $dateTo = Carbon::parse($filters['date_to'])->toDateString();

        $rows = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('stock_batches', 'stock_movements.batch_id', '=', 'stock_batches.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('stock_movements.pharmacy_id', $pharmacyId)
            ->where('stock_movements.movement_type', 'sale_out')
            ->whereBetween(DB::raw('DATE(stock_movements.created_at)'), [$dateFrom, $dateTo])
            ->groupBy(
                'products.id',
                'products.brand_name',
                'products.ar_name',
                'products.selling_price',
                'categories.name'
            )
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                products.selling_price,
                categories.name as category,
                SUM(ABS(stock_movements.quantity_change)) as total_units_sold,

                -- weighted average cost across all batches sold from
                SUM(ABS(stock_movements.quantity_change) * stock_batches.purchase_price)
                    / NULLIF(SUM(ABS(stock_movements.quantity_change)), 0) as avg_cost_price,

                -- total revenue
                SUM(ABS(stock_movements.quantity_change)) * products.selling_price as total_revenue,

                -- total cost
                SUM(ABS(stock_movements.quantity_change) * stock_batches.purchase_price) as total_cost
            ')
            ->orderByDesc('total_revenue')
            ->get();
        $totalRevenue = 0;
        $totalCost = 0;
        $products = $rows->map(function ($row) use (&$totalRevenue, &$totalCost) {
            $revenue = (float) $row->total_revenue;
            $cost = (float) $row->total_cost;
            $profit = round($revenue - $cost, 2);
            $avgCost = round((float) $row->avg_cost_price, 2);
            $sellingPrice = (float) $row->selling_price;
            $margin = $revenue > 0
                ? round((($revenue - $cost) / $revenue) * 100, 2)
                : 0.0;
            $totalRevenue += $revenue;
            $totalCost += $cost;

            return [
                'product_id' => $row->product_id,
                'brand_name' => $row->brand_name,
                'ar_name' => $row->ar_name,
                'category' => $row->category,
                'total_units_sold' => (int) $row->total_units_sold,
                // cost vs selling price comparison
                'avg_cost_price' => $avgCost,
                'selling_price' => $sellingPrice,
                'price_difference' => round($sellingPrice - $avgCost, 2),
                // profit
                'total_revenue' => round($revenue, 2),
                'total_cost' => round($cost, 2),
                'total_profit' => $profit,
                'profit_margin' => $margin,
            ];
        })->values()->all();
        $totalProfit = round($totalRevenue - $totalCost, 2);
        $overallMargin = $totalRevenue > 0
            ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2)
            : 0.0;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => $totalProfit,
                'overall_margin' => $overallMargin,
            ],
            'products' => $products,
        ];
    }
    public function getSupplierPrices(Pharmacy $pharmacy, array $filters): array
    {
        $pharmacyId = $pharmacy->id;

        $rows = DB::table('stock_batches')
            ->join('products', 'stock_batches.product_id', '=', 'products.id')
            ->join('purchase_invoices', 'stock_batches.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('stock_batches.pharmacy_id', $pharmacyId)
            ->whereNotNull('stock_batches.purchase_invoice_id')
            ->when(! empty($filters['product_id']),
                fn ($q) => $q->where('stock_batches.product_id', $filters['product_id'])
            )
            ->when(! empty($filters['date_from']),
                fn ($q) => $q->whereDate('purchase_invoices.invoice_date', '>=', $filters['date_from'])
            )
            ->when(! empty($filters['date_to']),
                fn ($q) => $q->whereDate('purchase_invoices.invoice_date', '<=', $filters['date_to'])
            )
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                categories.name as category,
                suppliers.id as supplier_id,
                suppliers.name as supplier_name,
                purchase_invoices.invoice_number,
                purchase_invoices.invoice_date,
                stock_batches.batch_number,
                stock_batches.purchase_price as unit_cost,
                stock_batches.selling_price as unit_selling_price
            ')
            ->orderBy('products.brand_name')
            ->orderBy('purchase_invoices.invoice_date')
            ->get();

        $grouped = $rows->groupBy('product_id')->map(function ($rows) {
            $first    = $rows->first();
            $purchases = $rows->map(fn ($r) => [
                'supplier_id'        => $r->supplier_id,
                'supplier_name'      => $r->supplier_name,
                'invoice_number'     => $r->invoice_number,
                'invoice_date'       => $r->invoice_date,
                'batch_number'       => $r->batch_number,
                'unit_cost'          => (float) $r->unit_cost,
                'unit_selling_price' => (float) $r->unit_selling_price,
                'margin'             => $r->unit_selling_price > 0
                    ? round((($r->unit_selling_price - $r->unit_cost) / $r->unit_selling_price) * 100, 2)
                    : 0.0,
            ])->values()->all();

            $costs = collect($purchases)->pluck('unit_cost');

            return [
                'product_id'  => $first->product_id,
                'brand_name'  => $first->brand_name,
                'ar_name'     => $first->ar_name,
                'category'    => $first->category,
                'min_cost'    => (float) $costs->min(),
                'max_cost'    => (float) $costs->max(),
                'avg_cost'    => round((float) $costs->avg(), 2),
                'purchases'   => $purchases,
            ];
        })->values()->all();

        return [
            'date_from' => $filters['date_from'] ?? null,
            'date_to'   => $filters['date_to'] ?? null,
            'products'  => $grouped,
        ];
    }
    public function getInventoryValue(Pharmacy $pharmacy): array
    {
        $pharmacyId = $pharmacy->id;

        $rows = DB::table('stock_batches')
            ->join('products', 'stock_batches.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('stock_batches.pharmacy_id', $pharmacyId)
            ->where('stock_batches.status', 'active')
            ->where('stock_batches.quantity_on_hand', '>', 0)
            ->groupBy('products.id', 'products.brand_name', 'products.ar_name', 'categories.name')
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                categories.name as category,
                SUM(stock_batches.quantity_on_hand) as total_quantity,
                SUM(stock_batches.quantity_on_hand * stock_batches.purchase_price) as cost_value,
                SUM(stock_batches.quantity_on_hand * stock_batches.selling_price) as selling_value
            ')
            ->orderBy('products.brand_name')
            ->get();

        $totalCost    = 0;
        $totalSelling = 0;

        $products = $rows->map(function ($row) use (&$totalCost, &$totalSelling) {
            $cost    = (float) $row->cost_value;
            $selling = (float) $row->selling_value;
            $totalCost    += $cost;
            $totalSelling += $selling;

            return [
                'product_id'       => $row->product_id,
                'brand_name'       => $row->brand_name,
                'ar_name'          => $row->ar_name,
                'category'         => $row->category,
                'total_quantity'   => (int) $row->total_quantity,
                'cost_value'       => round($cost, 2),
                'selling_value'    => round($selling, 2),
                'potential_profit' => round($selling - $cost, 2),
            ];
        })->values()->all();

        return [
            'summary' => [
                'total_cost_value'       => round($totalCost, 2),
                'total_selling_value'    => round($totalSelling, 2),
                'total_potential_profit' => round($totalSelling - $totalCost, 2),
                'overall_margin'         => $totalSelling > 0
                    ? round((($totalSelling - $totalCost) / $totalSelling) * 100, 2)
                    : 0.0,
            ],
            'products' => $products,
        ];
    }
    public function getStockHealth(Pharmacy $pharmacy, array $filters): array
    {
        $pharmacyId      = $pharmacy->id;
        $expiryDays      = (int) ($filters['expiry_days'] ?? 90);
        $today           = Carbon::today()->toDateString();
        $expiryThreshold = Carbon::today()->addDays($expiryDays)->toDateString();
        $deadCutoff      = Carbon::today()->subDays(90)->toDateString();

        // 1. Expiring soon
        $expiringSoon = DB::table('stock_batches')
            ->join('products', 'stock_batches.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('stock_batches.pharmacy_id', $pharmacyId)
            ->where('stock_batches.status', 'active')
            ->where('stock_batches.quantity_on_hand', '>', 0)
            ->whereNotNull('stock_batches.expiry_date')
            ->whereBetween('stock_batches.expiry_date', [$today, $expiryThreshold])
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                categories.name as category,
                stock_batches.id as batch_id,
                stock_batches.batch_number,
                stock_batches.expiry_date,
                stock_batches.quantity_on_hand,
                DATEDIFF(stock_batches.expiry_date, CURDATE()) as days_until_expiry,
                stock_batches.quantity_on_hand * stock_batches.purchase_price as stock_value
            ')
            ->orderBy('stock_batches.expiry_date')
            ->get()
            ->map(fn ($r) => [
                'product_id'        => $r->product_id,
                'brand_name'        => $r->brand_name,
                'ar_name'           => $r->ar_name,
                'category'          => $r->category,
                'batch_id'          => $r->batch_id,
                'batch_number'      => $r->batch_number,
                'expiry_date'       => $r->expiry_date,
                'days_until_expiry' => (int) $r->days_until_expiry,
                'quantity_on_hand'  => (int) $r->quantity_on_hand,
                'stock_value'       => round((float) $r->stock_value, 2),
            ])->values()->all();

        // 2. Low stock — active stock below min_stock
        $activeStock = DB::table('stock_batches')
            ->selectRaw('COALESCE(SUM(quantity_on_hand), 0)')
            ->whereColumn('product_id', 'products.id')
            ->where('status', 'active');

        $lowStock = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.pharmacy_id', $pharmacyId)
            ->whereNull('products.deleted_at')
            ->where('products.min_stock', '>', $activeStock)
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                categories.name as category,
                products.min_stock,
                (' . $activeStock->toSql() . ') as current_stock
            ')
            ->addBinding($activeStock->getBindings())
            ->orderByRaw('current_stock ASC')
            ->get()
            ->map(fn ($r) => [
                'product_id'    => $r->product_id,
                'brand_name'    => $r->brand_name,
                'ar_name'       => $r->ar_name,
                'category'      => $r->category,
                'current_stock' => (int) $r->current_stock,
                'min_stock'     => (int) $r->min_stock,
                'stock_status'  => (int) $r->current_stock === 0 ? 'out' : 'low',
            ])->values()->all();

        // 3. Dead stock — has active stock but no sale_out in last 90 days
        $soldProductIds = DB::table('stock_movements')
            ->where('pharmacy_id', $pharmacyId)
            ->where('movement_type', 'sale_out')
            ->whereDate('created_at', '>=', $deadCutoff)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        $deadStock = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.pharmacy_id', $pharmacyId)
            ->whereNull('products.deleted_at')
            ->whereNotIn('products.id', $soldProductIds)
            ->where($activeStock, '>', 0)
            ->selectRaw('
                products.id as product_id,
                products.brand_name,
                products.ar_name,
                categories.name as category,
                (' . $activeStock->toSql() . ') as current_stock
            ')
            ->addBinding($activeStock->getBindings())
            ->orderBy('products.brand_name')
            ->get()
            ->map(fn ($r) => [
                'product_id'    => $r->product_id,
                'brand_name'    => $r->brand_name,
                'ar_name'       => $r->ar_name,
                'category'      => $r->category,
                'current_stock' => (int) $r->current_stock,
            ])->values()->all();

        return [
            'expiry_days' => $expiryDays,
            'summary'     => [
                'expiring_soon_count' => count($expiringSoon),
                'low_stock_count'     => count($lowStock),
                'dead_stock_count'    => count($deadStock),
            ],
            'expiring_soon' => $expiringSoon,
            'low_stock'     => $lowStock,
            'dead_stock'    => $deadStock,
        ];
    }
    private function fillMissingPeriods(
        $data,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $period,
        string $groupFormat
    ): array {
        $result = [];
        if ($period === 'daily') {
            $carbonPeriod = CarbonPeriod::create($dateFrom, '1 day', $dateTo);
            foreach ($carbonPeriod as $date) {
                $label = $date->format('Y-m-d');
                $result[]  = $this->periodRow($label, $data->get($label));
            }
        } elseif ($period === 'weekly') {
            $current = $dateFrom->copy()->startOfWeek();
            while ($current->lte($dateTo)) {
                $label = $current->format('o') . '-W' . $current->format('W');
                $result[] = $this->periodRow($label, $data->get($label));
                $current->addWeek();
            }
        } elseif ($period === 'monthly') {
            $current = $dateFrom->copy()->startOfMonth();
            while ($current->lte($dateTo)) {
                $label = $current->format('Y-m');
                $result[] = $this->periodRow($label, $data->get($label));
                $current->addMonth();
            }
        }
        return $result;
    }
    private function periodRow(string $label, $row): array
    {
        return [
            'period_label' => $label,
            'invoice_count' => $row ? (int) $row->invoice_count : 0,
            'revenue' => $row ? (float) $row->revenue : 0.0,
            'discount' => $row ? (float) $row->discount : 0.0,
            'tax' => $row ? (float) $row->tax : 0.0,
            'collected' => $row ? (float) $row->collected : 0.0,
            'outstanding' => $row ? (float) $row->outstanding : 0.0,
        ];
    }
}
