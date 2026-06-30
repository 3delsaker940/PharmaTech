<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Category::query()
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->withCount('products')
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

}
