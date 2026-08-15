<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierReturnInvoiceRequest;
use App\Http\Resources\SupplierReturnInvoiceResource;
use App\Models\SupplierReturnInvoice;
use App\Services\SupplierReturnInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierReturnInvoiceController extends Controller
{
    public function __construct(
        private readonly SupplierReturnInvoiceService $supplierReturnInvoiceService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->supplierReturnInvoiceService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['status', 'supplier_id', 'date_from', 'date_to', 'per_page'])
        );
        return SupplierReturnInvoiceResource::collection($invoices);
    }

    public function store(StoreSupplierReturnInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->supplierReturnInvoiceService->store(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated()
        );
        return (new SupplierReturnInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, SupplierReturnInvoice $supplierReturnInvoice): SupplierReturnInvoiceResource
    {
        $this->authorize('view', $supplierReturnInvoice);
        $supplierReturnInvoice->load(['items.product', 'supplier', 'originalPurchaseInvoice', 'createdBy']);
        return new SupplierReturnInvoiceResource($supplierReturnInvoice);
    }

    public function cancel(Request $request, SupplierReturnInvoice $supplierReturnInvoice): SupplierReturnInvoiceResource
    {
        $this->authorize('cancel', $supplierReturnInvoice);
        $cancelled = $this->supplierReturnInvoiceService->cancel(
            $supplierReturnInvoice,
            $request->user()
        );
        return new SupplierReturnInvoiceResource($cancelled);
    }
}
