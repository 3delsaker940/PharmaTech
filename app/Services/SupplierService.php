<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return Supplier::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function store(Pharmacy $pharmacy, array $data): Supplier
    {
        return Supplier::create([
            'pharmacy_id' => $pharmacy->id,
            'status'      => 'active',
            ...$data,
        ]);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update(array_intersect_key($data, array_flip([
            'name', 'phone', 'email', 'address', 'notes',
        ])));

        return $supplier->fresh();
    }

    public function deactivate(Supplier $supplier): Supplier
    {
        $supplier->update(['status' => 'inactive']);

        return $supplier->fresh();
    }

    public function activate(Supplier $supplier): Supplier
    {
        $supplier->update(['status' => 'active']);

        return $supplier->fresh();
    }
}
