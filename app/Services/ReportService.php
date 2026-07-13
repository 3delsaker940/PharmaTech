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
