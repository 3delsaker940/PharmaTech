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
    use AuthorizesPharmacyResource;

    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBoxes = CashBox::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->with(['openedBy', 'closedBy'])
            ->orderByDesc('opened_at')
            ->paginate((int) $request->input('per_page', 15));

        return CashBoxResource::collection($cashBoxes);
    }

    public function store(StoreCashBoxRequest $request): JsonResponse
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::create([
            'pharmacy_id'     => $pharmacy->id,
            'name'            => $request->validated('name'),
            'opening_balance' => $request->validated('opening_balance'),
            'current_balance' => $request->validated('opening_balance'),
            'status'          => 'active',
            'opened_by'       => $request->user()->id,
            'opened_at'       => now(),
        ]);

        return (new CashBoxResource($cashBox->load('openedBy')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, CashBox $cashBox): CashBoxResource
    {
        $this->authorizePharmacyResource($request, $cashBox->pharmacy_id);

        $cashBox->load(['openedBy', 'closedBy']);

        return new CashBoxResource($cashBox);
    }

    public function transactions(Request $request, CashBox $cashBox): AnonymousResourceCollection
    {
        $this->authorizePharmacyResource($request, $cashBox->pharmacy_id);

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
