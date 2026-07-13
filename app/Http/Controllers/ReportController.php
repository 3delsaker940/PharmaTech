<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}
    public function profit(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);
        $data = $this->reportService->getProfitReport(
            $request->attributes->get('pharmacy'),
            $request->only(['date_from', 'date_to'])
        );

        return response()->json(['data' => $data], 200);
    }
    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $data = $this->reportService->getTopProducts(
            $request->attributes->get('pharmacy'),
            $request->only(['date_from', 'date_to', 'limit'])
        );
        return response()->json(['data' => $data], 200);
    }
    public function supplierPrices(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ]);
        $data = $this->reportService->getSupplierPrices(
            $request->attributes->get('pharmacy'),
            $request->only(['product_id', 'date_from', 'date_to'])
        );
        return response()->json(['data' => $data], 200);
    }
    public function inventoryValue(Request $request): JsonResponse
    {
        $data = $this->reportService->getInventoryValue(
            $request->attributes->get('pharmacy')
        );
        return response()->json(['data' => $data], 200);
    }
    public function stockHealth(Request $request): JsonResponse
    {
        $request->validate([
            'expiry_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);
        $data = $this->reportService->getStockHealth(
            $request->attributes->get('pharmacy'),
            $request->only(['expiry_days'])
        );
        return response()->json(['data' => $data], 200);
    }
    public function sales(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['required', 'in:daily,weekly,monthly'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);
        $data = $this->reportService->getSalesReport(
            $request->attributes->get('pharmacy'),
            $request->only(['period', 'date_from', 'date_to'])
        );
        return response()->json(['data' => $data], 200);
    }
}
