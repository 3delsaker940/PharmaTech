<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Resources\StockBatchResource;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockBatchController extends Controller
{
    use AuthorizesPharmacyResource;

    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $batches = StockBatch::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->when(
                $request->filled('product_id'),
                fn ($q) => $q->where('product_id', $request->input('product_id'))
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->when(
                $request->boolean('expiring_soon'),
                fn ($q) => $q->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', now()->addDays(30))
                    ->whereDate('expiry_date', '>=', now())
            )
            ->with('product')
            ->orderBy('expiry_date IS NULL, expiry_date ASC')
            ->paginate((int) $request->input('per_page', 15));

        return StockBatchResource::collection($batches);
    }

    public function show(Request $request, StockBatch $stockBatch): StockBatchResource
    {
        $this->authorizePharmacyResource($request, $stockBatch->pharmacy_id);

        $stockBatch->load('product');

        return new StockBatchResource($stockBatch);
    }
}
