<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseInvoiceController extends Controller
{
    use AuthorizesPharmacyResource;

    public function __construct(private readonly PurchaseInvoiceService $purchaseInvoiceService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->purchaseInvoiceService->list(
            $request->attributes->get('pharmacy'),
            $request->only([
                'supplier_id', 'status', 'payment_status',
                'from_date', 'to_date', 'per_page',
            ])
        );

        return PurchaseInvoiceResource::collection($invoices);
    }

    public function store(StorePurchaseInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->purchaseInvoiceService->store(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated()
        );

        return (new PurchaseInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        $this->authorizePharmacyResource($request, $purchaseInvoice->pharmacy_id);

        $purchaseInvoice->load([
            'supplier',
            'createdBy',
            'items.product',
            'supplierDebt.payments.createdBy',
        ]);

        return new PurchaseInvoiceResource($purchaseInvoice);
    }

    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        $this->authorizePharmacyResource($request, $purchaseInvoice->pharmacy_id);

        $updated = $this->purchaseInvoiceService->update($purchaseInvoice, $request->validated());

        return new PurchaseInvoiceResource($updated);
    }

    public function cancel(Request $request, PurchaseInvoice $purchaseInvoice): PurchaseInvoiceResource
    {
        $this->authorizePharmacyResource($request, $purchaseInvoice->pharmacy_id);

        $cancelled = $this->purchaseInvoiceService->cancel($purchaseInvoice, $request->user());

        return new PurchaseInvoiceResource($cancelled);
    }
}
