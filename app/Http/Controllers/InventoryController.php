<?php

namespace App\Http\Controllers;

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
        $stockBatches = $product->stockBatches;
        return response()->json($stockBatches, 200);
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
