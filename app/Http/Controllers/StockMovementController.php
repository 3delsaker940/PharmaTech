<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $movements = StockMovement::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->when(
                $request->filled('product_id'),
                fn ($q) => $q->where('product_id', $request->input('product_id'))
            )
            ->when(
                $request->filled('movement_type'),
                fn ($q) => $q->where('movement_type', $request->input('movement_type'))
            )
            ->with(['product', 'createdBy', 'batch'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function show(Request $request, StockMovement $stockMovement): StockMovementResource
    {
        $this->authorize('view', $stockMovement);

        $stockMovement->load(['product', 'createdBy', 'batch']);

        return new StockMovementResource($stockMovement);
    }
}
