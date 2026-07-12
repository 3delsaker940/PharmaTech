<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    use AuthorizesPharmacyResource;

    public function index(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');
        $customers = Customer::where('pharmacy_id', $pharmacy->id)
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request) {
                    $term = '%' . $request->input('search') . '%';
                    $inner->where('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                })
            )
            ->when($request->boolean('with_trashed'), fn ($q) => $q->withTrashed())
            ->orderBy('full_name')
            ->paginate((int) $request->input('per_page', 15));

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create([
            'pharmacy_id' => $request->attributes->get('pharmacy')->id,
            ...$request->validated(),
        ]);
        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->authorizePharmacyResource($request, $customer->pharmacy_id);
        return new CustomerResource($customer);
    }
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $this->authorizePharmacyResource($request, $customer->pharmacy_id);
        $customer->update($request->validated());
        return new CustomerResource($customer->fresh());
    }
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizePharmacyResource($request, $customer->pharmacy_id);
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully.'], 200);
    }
    public function restore(Request $request, Customer $customer): CustomerResource
    {
        $this->authorizePharmacyResource($request, $customer->pharmacy_id);
        $customer->restore();
        return new CustomerResource($customer->fresh());
    }
}
