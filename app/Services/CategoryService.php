<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Pharmacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function list(array $filters = [], ?Pharmacy $pharmacy = null): LengthAwarePaginator
    {
        $query= Category::query()
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            );
        if ($pharmacy !== null) {
            $query->withCount([
                'products' => fn ($q) => $q
                    ->where('pharmacy_id', $pharmacy->id)
                    ->whereNull('deleted_at'),
            ]);
        }
        return $query
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

}
