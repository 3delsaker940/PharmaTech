<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashBoxRequest;
use App\Http\Resources\CashBoxResource;
use App\Http\Resources\CashTransactionResource;
use App\Models\CashBox;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashBoxController extends Controller
{
    private const INCOMING_TRANSACTION_TYPES = [
        'sale_in',
        'supplier_return_in',
        'customer_debt_payment_in',
        'manual_in',
    ];

    private const OUTGOING_TRANSACTION_TYPES = [
        'purchase_out',
        'customer_return_out',
        'supplier_debt_payment_out',
        'manual_out',
    ];

    public function store(StoreCashBoxRequest $request): JsonResponse
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::create([
            'pharmacy_id' => $pharmacy->id,
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
    public function transactions(Request $request)
    {
        $pharmacy = $request->attributes->get('pharmacy');
        $cashBox = CashBox::where('pharmacy_id', $pharmacy->id)->firstOrFail();

        $transactions = $cashBox->transactions()
            ->when($request->filled('transaction_type'), function ($q) use ($request) {
                $q->where('transaction_type', $request->input('transaction_type'));
            })
            ->when($request->filled('reference_type'), function ($q) use ($request) {
                $q->where('reference_type', $request->input('reference_type'));
            })
            ->when($request->filled('date'), function ($q) use ($request) {
                $q->whereDate('transaction_time', $request->input('date'));
            })
            ->when(! $request->filled('date') && $request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('transaction_time', '>=', $request->input('date_from'));
            })
            ->when(! $request->filled('date') && $request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('transaction_time', '<=', $request->input('date_to'));
            })
            ->with('createdBy')
            ->orderByDesc('transaction_time')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 15));

        return CashTransactionResource::collection($transactions);
    }

    public function statistics(Request $request): JsonResponse
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $cashBox = CashBox::where('pharmacy_id', $pharmacy->id)->firstOrFail();

        $now = now();
        $periods = [
            'today' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
        ];

        $statistics = [];

        foreach ($periods as $period => $start) {
            $statistics[$period] = [
                'in' => $this->sumTransactions($cashBox, self::INCOMING_TRANSACTION_TYPES, $start, $now),
                'out' => $this->sumTransactions($cashBox, self::OUTGOING_TRANSACTION_TYPES, $start, $now),
            ];
        }

        return response()->json($statistics, 200);
    }

    private function sumTransactions(
        CashBox $cashBox,
        array $transactionTypes,
        CarbonInterface $start,
        CarbonInterface $end
    ): float {
        return round((float) $cashBox->transactions()
            ->whereIn('transaction_type', $transactionTypes)
            ->whereBetween('transaction_time', [$start, $end])
            ->sum('amount'), 2);
    }
}
