<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Pharmacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return Category::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->withCount('products')
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function store(Pharmacy $pharmacy, array $data): Category
    {
        return Category::create([
            'pharmacy_id' => $pharmacy->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'status'      => 'active',
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update(array_intersect_key($data, array_flip(['name', 'description'])));

        return $category->fresh();
    }

    public function deactivate(Category $category): Category
    {
        $category->update(['status' => 'inactive']);

        return $category->fresh();
    }

    public function activate(Category $category): Category
    {
        $category->update(['status' => 'active']);

        return $category->fresh();
    }
}
