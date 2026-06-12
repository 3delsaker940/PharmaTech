<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    use AuthorizesPharmacyResource;

    public function __construct(private readonly SupplierService $supplierService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $suppliers = $this->supplierService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['search', 'with_trashed', 'per_page'])
        );

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->store(
            $request->attributes->get('pharmacy'),
            $request->validated()
        );

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Supplier $supplier): SupplierResource
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        $updated = $this->supplierService->update($supplier, $request->validated());

        return new SupplierResource($updated);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        $this->supplierService->delete($supplier);

        return response()->json(['message' => 'Supplier deleted successfully.'], 200);
    }

    public function restore(Request $request, Supplier $supplier): SupplierResource
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        $restored = $this->supplierService->restore($supplier);

        return new SupplierResource($restored);
    }
}
