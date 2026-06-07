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
            $request->only(['search', 'status', 'per_page'])
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

    public function deactivate(Request $request, Supplier $supplier): SupplierResource
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        return new SupplierResource($this->supplierService->deactivate($supplier));
    }

    public function activate(Request $request, Supplier $supplier): SupplierResource
    {
        $this->authorizePharmacyResource($request, $supplier->pharmacy_id);

        return new SupplierResource($this->supplierService->activate($supplier));
    }
}
