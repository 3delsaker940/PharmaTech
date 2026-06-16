<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Resources\StockBatchResource;
use App\Models\StockBatch;
use App\Models\StockMovement;
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
            ->orderByRaw('ISNULL(expiry_date), expiry_date ASC')
            ->paginate((int) $request->input('per_page', 15));

        return StockBatchResource::collection($batches);
    }

    public function show(Request $request, StockBatch $stockBatch): StockBatchResource
    {
        $this->authorizePharmacyResource($request, $stockBatch->pharmacy_id);

        $stockBatch->load('product');

        return new StockBatchResource($stockBatch);
    }

    public function markExpired(Request $request, StockBatch $stockBatch): StockBatchResource
    {
        $this->authorizePharmacyResource($request, $stockBatch->pharmacy_id);

        if ($stockBatch->status === 'expired') {
            throw new \InvalidArgumentException('This batch is already marked as expired.');
        }

        if ($stockBatch->status === 'inactive') {
            throw new \InvalidArgumentException('This batch has been reversed and cannot be expired.');
        }

        $quantityWrittenOff = $stockBatch->quantity_on_hand;

        $stockBatch->update([
            'status'           => 'expired',
            'quantity_on_hand' => 0,
        ]);

        if ($quantityWrittenOff > 0) {
            StockMovement::create([
                'pharmacy_id'     => $stockBatch->pharmacy_id,
                'product_id'      => $stockBatch->product_id,
                'batch_id'        => $stockBatch->id,
                'movement_type'   => 'expiry_out',
                'quantity_change' => -$quantityWrittenOff,
                'reference_type'  => null,
                'reference_id'    => null,
                'created_by'      => $request->user()->id,
                'notes'           => "Batch {$stockBatch->batch_number} manually marked as expired",
            ]);
        }

        $stockBatch->load('product');

        return new StockBatchResource($stockBatch);
    }
}
