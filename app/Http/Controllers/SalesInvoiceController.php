<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Http\Requests\UpdateSalesInvoiceRequest;
use App\Http\Requests\IndexSalesInvoiceRequest;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\SalesInvoice;
use App\Services\SalesInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesInvoiceController extends Controller
{
    public function __construct(private readonly SalesInvoiceService $salesInvoiceService) {}

    public function index(IndexSalesInvoiceRequest $request): AnonymousResourceCollection
    {
        $invoices = $this->salesInvoiceService->list(
            $request->attributes->get('pharmacy'),
            $request->only([
                'status',
                'payment_status',
                'payment_method',
                'customer_id',
                'walk_in',
                'date_from',
                'date_to',
                'per_page',
            ])
        );
        return SalesInvoiceResource::collection($invoices);
    }

    public function store(StoreSalesInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->salesInvoiceService->store(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated()
        );
        return (new SalesInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, SalesInvoice $salesInvoice): SalesInvoiceResource
    {
        $this->authorize('view', $salesInvoice);
        $salesInvoice->load(['items.product', 'customer', 'customerDebt', 'createdBy']);
        return new SalesInvoiceResource($salesInvoice);
    }

    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice): SalesInvoiceResource
    {
        // Ownership already verified in UpdateSalesInvoiceRequest::authorize()
        if ($salesInvoice->status === 'cancelled') {
            abort(422, 'Cannot update a cancelled invoice.');
        }
        $updated = $this->salesInvoiceService->update($salesInvoice, $request->validated());
        return new SalesInvoiceResource($updated);
    }

    public function cancel(Request $request, SalesInvoice $salesInvoice): SalesInvoiceResource
    {
        $this->authorize('cancel', $salesInvoice);
        $cancelled = $this->salesInvoiceService->cancel($salesInvoice, $request->user());
        return new SalesInvoiceResource($cancelled);
    }
}
