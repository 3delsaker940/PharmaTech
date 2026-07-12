<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\StoreCustomerReturnInvoiceRequest;
use App\Http\Resources\CustomerReturnInvoiceResource;
use App\Models\CustomerReturnInvoice;
use App\Services\CustomerReturnInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerReturnInvoiceController extends Controller
{
    use AuthorizesPharmacyResource;

    public function __construct(
        private readonly CustomerReturnInvoiceService $customerReturnInvoiceService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->customerReturnInvoiceService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['status', 'customer_id', 'date_from', 'date_to', 'per_page'])
        );
        return CustomerReturnInvoiceResource::collection($invoices);
    }

    public function store(StoreCustomerReturnInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->customerReturnInvoiceService->store(
            $request->attributes->get('pharmacy'),
            $request->user(),
            $request->validated()
        );

        return (new CustomerReturnInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, CustomerReturnInvoice $customerReturnInvoice): CustomerReturnInvoiceResource
    {
        $this->authorizePharmacyResource($request, $customerReturnInvoice->pharmacy_id);
        $customerReturnInvoice->load(['items.product', 'customer', 'originalSalesInvoice', 'createdBy']);
        return new CustomerReturnInvoiceResource($customerReturnInvoice);
    }

    public function cancel(Request $request, CustomerReturnInvoice $customerReturnInvoice): CustomerReturnInvoiceResource
    {
        $this->authorizePharmacyResource($request, $customerReturnInvoice->pharmacy_id);

        $cancelled = $this->customerReturnInvoiceService->cancel(
            $customerReturnInvoice,
            $request->user()
        );
        return new CustomerReturnInvoiceResource($cancelled);
    }
}
