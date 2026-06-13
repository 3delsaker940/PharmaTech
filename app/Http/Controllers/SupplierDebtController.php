<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Resources\SupplierDebtResource;
use App\Models\SupplierDebt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierDebtController extends Controller
{
    use AuthorizesPharmacyResource;

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
        $this->authorizePharmacyResource($request, $supplierDebt->pharmacy_id);

        $supplierDebt->load(['supplier', 'payments.createdBy']);

        return new SupplierDebtResource($supplierDebt);
    }
}
