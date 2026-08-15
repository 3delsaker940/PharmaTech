<?php

namespace App\Http\Controllers;

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
        $this->authorize('view', $supplier);
        $supplier->load('company');
        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        // Ownership already verified in UpdateSupplierRequest::authorize()

        $updated = $this->supplierService->update($supplier, $request->validated());

        return new SupplierResource($updated->load('company'));
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->supplierService->delete($supplier);

        return response()->json(['message' => 'Supplier deleted successfully.'], 200);
    }

    public function restore(Request $request, Supplier $supplier): SupplierResource
    {
        $this->authorize('restore', $supplier);

        $restored = $this->supplierService->restore($supplier);

        return new SupplierResource($restored);
    }
}
