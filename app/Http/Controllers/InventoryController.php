<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockBatchResource;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getAllPharmacyProducts(Request $request)
    {
        $pharmacyId = $request->attributes->get('pharmacy')->id;
        $products = Product::where('pharmacy_id', $pharmacyId)
            ->withTotalQuantity()
            ->get();
        return response()->json($products, 200);
    }
    public function getProductStockBatches(Request $request, $productId)
    {
        $pharmacyId = $request->attributes->get('pharmacy')->id;
        $product = Product::where('pharmacy_id', $pharmacyId)
            ->where('id', $productId)
            ->firstOrFail();
        $batches = $product->stockBatches()
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->orderByDesc('received_at')
            ->paginate((int) $request->input('per_page', 15));
        return StockBatchResource::collection($batches);
    }

    public function getProductsByCategory(Request $request, $categoryId)
    {
        $pharmacyId = $request->attributes->get('pharmacy')->id;
        $products = Product::where('pharmacy_id', $pharmacyId)
            ->where('category_id', $categoryId)
            ->withTotalQuantity()
            ->get();
        return response()->json($products, 200);
    }
}
