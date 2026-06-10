<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        $query = Supplier::query()
            ->where('pharmacy_id', $pharmacy->id);

        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        return $query
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function store(Pharmacy $pharmacy, array $data): Supplier
    {
        return Supplier::create([
            'pharmacy_id' => $pharmacy->id,
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

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function restore(Supplier $supplier): Supplier
    {
        $supplier->restore();

        return $supplier->fresh();
    }
}
