<?php

namespace App\Services;

use App\Models\CustomerReturnInvoice;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockBatch;
use App\Models\SupplierReturnInvoice;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // header
    public function getHeader(Pharmacy $pharmacy): array
    {
        $pharmacyId = $pharmacy->id;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayStats = SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $today)
            ->selectRaw('
                COUNT(*) as invoice_count,
                COALESCE(SUM(grand_total), 0) as revenue,
                COALESCE(SUM(grand_total) / NULLIF(COUNT(*), 0), 0) as avg_invoice
            ')
            ->first();

        $todayRevenue = (float) $todayStats->revenue;
        $todayInvoiceCount = (int)   $todayStats->invoice_count;
        $todayAvgInvoice = round((float) $todayStats->avg_invoice, 2);

        $todayUnitsSold = (int) SalesInvoiceItem::whereHas('salesInvoice', fn ($q) =>
        $q->where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $today)
        )->sum('quantity');

        $yesterdayRevenue = (float) SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $yesterday)
            ->sum('grand_total');

        return [
            'today_revenue' => $todayRevenue,
            'today_revenue_change_percent' => $this->percentChange($yesterdayRevenue, $todayRevenue),
            'today_invoice_count' => $todayInvoiceCount,
            'today_avg_invoice' => $todayAvgInvoice,
            'today_units_sold' => $todayUnitsSold,
        ];
    }

    // cards
    public function getCards(Pharmacy $pharmacy): array
    {
        $pharmacyId = $pharmacy->id;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $yesterdayRevenue = (float) SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $yesterday)
            ->sum('grand_total');

        $sameDayLastWeek = (float) SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $yesterday->copy()->subDays(7))
            ->sum('grand_total');

        $totalProducts = (int) Product::where('pharmacy_id', $pharmacyId)
            ->whereNull('deleted_at')
            ->count();

        $inStockProducts = (int) Product::where('pharmacy_id', $pharmacyId)
            ->whereNull('deleted_at')
            ->whereHas('stockBatches', fn ($q) =>
            $q->where('status', 'active')->where('quantity_on_hand', '>', 0)
            )->count();

        $activeStockSubquery = DB::table('stock_batches')
            ->selectRaw('COALESCE(SUM(quantity_on_hand), 0)')
            ->whereColumn('product_id', 'products.id')
            ->where('status', 'active');

        $stockAlertsCount = (int) Product::where('pharmacy_id', $pharmacyId)
            ->whereNull('deleted_at')
            ->where('min_stock', '>', $activeStockSubquery)
            ->count();

        $outOfStockCount = (int) Product::where('pharmacy_id', $pharmacyId)
            ->whereNull('deleted_at')
            ->whereDoesntHave('stockBatches', fn ($q) =>
            $q->where('status', 'active')->where('quantity_on_hand', '>', 0)
            )->count();

        $todayInvoiceCount = (int) SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $today)
            ->count();

        $yesterdayInvoiceCount = (int) SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereDate('invoice_date', $yesterday)
            ->count();

        return [
            'yesterday_revenue' => $yesterdayRevenue,
            'yesterday_week_change_percent' => $this->percentChange($sameDayLastWeek, $yesterdayRevenue),

            'total_products' => $totalProducts,
            'in_stock_products' => $inStockProducts,

            'stock_alerts_count' => $stockAlertsCount,
            'out_of_stock_count' => $outOfStockCount,

            'today_sales_count' => $todayInvoiceCount,
            'today_sales_change_percent' => $this->percentChange($yesterdayInvoiceCount, $todayInvoiceCount),
        ];
    }

    //weekly revenue
    public function getWeeklyRevenue(Pharmacy $pharmacy): array
    {
        $pharmacyId = $pharmacy->id;
        $today = Carbon::today();
        $weekAgo = Carbon::today()->subDays(6);

        $rows = SalesInvoice::where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereBetween('invoice_date', [$weekAgo->toDateString(), $today->toDateString()])
            ->selectRaw('DATE(invoice_date) as date, COALESCE(SUM(grand_total), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $chart[] = [
                'date' => $date,
                'day' => Carbon::parse($date)->format('D'),
                'revenue' => isset($rows[$date]) ? (float) $rows[$date]->revenue : 0.0,
                'is_today' => $i === 0,
            ];
        }
        return $chart;
    }

    //transactions
    public function getTransactions(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        $pharmacyId = $pharmacy->id;
        $perPage = (int) ($filters['per_page'] ?? 15);

        $purchases = DB::table('purchase_invoices')
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', '!=', 'cancelled')
            ->select([
                'id',
                DB::raw("'purchase' as type"),
                'invoice_number',
                'invoice_date',
                DB::raw('grand_total as amount'),
                'payment_status',
                'created_at',
            ]);

        $sales = DB::table('sales_invoices')
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', '!=', 'cancelled')
            ->select([
                'id',
                DB::raw("'sale' as type"),
                'invoice_number',
                'invoice_date',
                DB::raw('grand_total as amount'),
                'payment_status',
                'created_at',
            ]);

        $customerReturns = DB::table('customer_return_invoices')
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', '!=', 'cancelled')
            ->select([
                'id',
                DB::raw("'customer_return' as type"),
                'invoice_number',
                'invoice_date',
                DB::raw('refund_total as amount'),
                DB::raw("'completed' as payment_status"),
                'created_at',
            ]);

        $supplierReturns = DB::table('supplier_return_invoices')
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', '!=', 'cancelled')
            ->select([
                'id',
                DB::raw("'supplier_return' as type"),
                'invoice_number',
                'invoice_date',
                DB::raw('refund_total as amount'),
                DB::raw("'completed' as payment_status"),
                'created_at',
            ]);

        $typeFilter = $filters['type'] ?? null;

        $queries = [];
        if (! $typeFilter || $typeFilter === 'purchase') $queries[] = $purchases;
        if (! $typeFilter || $typeFilter === 'sale') $queries[] = $sales;
        if (! $typeFilter || $typeFilter === 'customer_return') $queries[] = $customerReturns;
        if (! $typeFilter || $typeFilter === 'supplier_return') $queries[] = $supplierReturns;

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union = $union->union($query);
        }
        return DB::table(DB::raw("({$union->toSql()}) as transactions"))
            ->mergeBindings($union)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function percentChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return round((($new - $old) / $old) * 100, 1);
    }
}
