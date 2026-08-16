<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordSupplierDebtPaymentRequest;
use App\Http\Resources\SupplierDebtResource;
use App\Models\SupplierDebt;
use App\Services\SupplierDebtService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierDebtController extends Controller
{
    public function __construct(private readonly SupplierDebtService $supplierDebtService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');

        $debts = SupplierDebt::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->when(
                $request->filled('supplier_id'),
                fn ($q) => $q->where('supplier_id', $request->input('supplier_id'))
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status'))
            )
            ->with('supplier')
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        return SupplierDebtResource::collection($debts);
    }

    public function show(Request $request, SupplierDebt $supplierDebt): SupplierDebtResource
    {
        $this->authorize('view', $supplierDebt);

        $supplierDebt->load(['supplier', 'payments.createdBy']);

        return new SupplierDebtResource($supplierDebt);
    }
    public function pay(
        RecordSupplierDebtPaymentRequest $request,
        SupplierDebt                     $supplierDebt
    ): SupplierDebtResource {
        // Ownership already verified in RecordSupplierDebtPaymentRequest::authorize()

        $updated = $this->supplierDebtService->recordPayment(
            $supplierDebt,
            $request->user(),
            $request->validated()
        );

        return new SupplierDebtResource($updated);
    }
}
