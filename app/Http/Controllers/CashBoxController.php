<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\StoreCashBoxRequest;
use App\Http\Resources\CashBoxResource;
use App\Http\Resources\CashTransactionResource;
use App\Models\CashBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashBoxController extends Controller
{
    public function store(StoreCashBoxRequest $request): JsonResponse
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::create([
            'pharmacy_id'     => $pharmacy->id,
            'opening_balance' => $request->validated('opening_balance'),
            'current_balance' => $request->validated('opening_balance'),
        ]);

        return (new CashBoxResource($cashBox))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request): CashBoxResource
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::where('pharmacy_id', $pharmacy->id)
            ->firstOrFail();

        return new CashBoxResource($cashBox);
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::where('pharmacy_id', $pharmacy->id)->firstOrFail();

        $transactions = $cashBox->transactions()
            ->when(
                $request->filled('transaction_type'),
                fn ($q) => $q->where('transaction_type', $request->input('transaction_type'))
            )
            ->with('createdBy')
            ->orderByDesc('transaction_time')
            ->paginate((int) $request->input('per_page', 15));

        return CashTransactionResource::collection($transactions);
    }
}
