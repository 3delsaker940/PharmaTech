<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkStockAdjustmentRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $movements = StockMovement::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
            ->when(
                $request->filled('product_id'),
                fn ($q) => $q->where('product_id', $request->input('product_id'))
            )
            ->with(['product', 'createdBy', 'batch'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $result = $this->stockService->manualAdjustment(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated()
        );

        return (new StockAdjustmentResource($result))
            ->response()
            ->setStatusCode(201);
    }

    public function bulkStore(BulkStockAdjustmentRequest $request): JsonResponse
    {
        $results = $this->stockService->bulkAdjustment(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated('items')
        );

        return StockAdjustmentResource::collection($results)
            ->response()
            ->setStatusCode(201);
    }
}
