<?php

namespace App\Services;
use App\Models\Pharmacy;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('pharmacy_id', $pharmacy->id);

        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        return $query
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where(function ($inner) use ($filters) {
                    $term = '%' . $filters['search'] . '%';
                    $inner->where('brand_name', 'like', $term)
                        ->orWhere('scientific_name', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                })
            )
            ->when(
                filled($filters['category_id'] ?? null),
                fn ($q) => $q->where('category_id', $filters['category_id'])
            )
            ->when(
                isset($filters['prescription_required']),
                fn ($q) => $q->where(
                    'prescription_required',
                    filter_var($filters['prescription_required'], FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->when(
                $filters['in_stock'] ?? null,
                fn ($q) => $q->whereHas('stockBatches', fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0))
            )
            ->withTotalQuantity()
            ->with('category')
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findByBarcode(Pharmacy $pharmacy, string $barcode): ?Product
    {
        return Product::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->where('barcode', $barcode)
            ->with(['category', 'medicalInfo'])
            ->first();
    }

    public function store(Pharmacy $pharmacy, array $data): Product
    {
        return Product::create([
            'pharmacy_id' => $pharmacy->id,
            ...$data,
        ]);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update(array_intersect_key($data, array_flip([
            'category_id', 'barcode', 'brand_name', 'scientific_name',
            'prescription_required', 'buying_price', 'selling_price',
            'tax_rate', 'discount_rate', 'min_stock','image_path',
            'base_unit', 'selling_unit', 'units_per_base', 'allow_partial_selling',
        ])));

        return $product->fresh(['category']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): Product
    {
        $product->restore();

        return $product->fresh(['category']);
    }
}
