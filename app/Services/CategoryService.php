<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Pharmacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()
            ->where('pharmacy_id', $pharmacy->id);

        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        return $query
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
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
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update(array_intersect_key($data, array_flip(['name', 'description'])));

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->fresh();
    }
}
