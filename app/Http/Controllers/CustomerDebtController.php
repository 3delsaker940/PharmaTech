<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordCustomerDebtPaymentRequest;
use App\Http\Resources\CustomerDebtResource;
use App\Models\CustomerDebt;
use App\Services\CustomerDebtService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerDebtController extends Controller
{
    public function __construct(private readonly CustomerDebtService $customerDebtService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $debts = $this->customerDebtService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['customer_id', 'status', 'per_page'])
        );
        return CustomerDebtResource::collection($debts);
    }

    public function show(Request $request, CustomerDebt $customerDebt): CustomerDebtResource
    {
        $this->authorize('view', $customerDebt);
        $customerDebt->load(['customer', 'payments.createdBy', 'salesInvoice']);
        return new CustomerDebtResource($customerDebt);
    }

    public function pay(
        RecordCustomerDebtPaymentRequest $request,
        CustomerDebt$customerDebt
    ): CustomerDebtResource {
        // Ownership already verified in RecordCustomerDebtPaymentRequest::authorize()

        $updated = $this->customerDebtService->recordPayment(
            $customerDebt,
            $request->user(),
            $request->validated()
        );
        return new CustomerDebtResource($updated);
    }
}
