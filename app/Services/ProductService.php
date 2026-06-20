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
            ->when($filters['sort_by'] ?? null, function ($q, $v) {
                match ($v) {
                    'name_asc' => $q->orderBy('brand_name', 'asc'),
                    'name_desc' => $q->orderBy('brand_name', 'desc'),
                    'price_asc' => $q->orderBy('selling_price', 'asc'),
                    'price_desc' => $q->orderBy('selling_price', 'desc'),
                    'stock_asc'  => $q->orderByRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) ASC'),
                    'stock_desc' => $q->orderByRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) DESC'),
                    'expiry_asc' => $q->orderByRaw('(SELECT MIN(expiry_date) FROM stock_batches WHERE product_id = products.id AND status = "active" AND expiry_date IS NOT NULL) ASC'),
                    'expiry_desc' => $q->orderByRaw('(SELECT MIN(expiry_date) FROM stock_batches WHERE product_id = products.id AND status = "active" AND expiry_date IS NOT NULL) DESC'),
                    default => $q->orderByDesc('id'),
                };
            })
           ->when(! isset($filters['sort_by']), fn ($q) => $q->orderBy('brand_name', 'asc'))
            ->when($filters['stock_status'] ?? null, function ($q, $v) {
                match ($v) {
                    'out'       => $q->whereRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) = 0'),
                    'low'       => $q->whereRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) > 0')
                        ->whereRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) < min_stock'),
                    'available' => $q->whereRaw('COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0) >= min_stock'),
                    default     => null,
                };
            })
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '<=', $v))
            ->when($filters['expiry_filter'] ?? null, function ($q, $v) {
                $subquery = 'SELECT MIN(expiry_date) FROM stock_batches WHERE product_id = products.id AND status = "active" AND expiry_date IS NOT NULL';

                match ($v) {
                    'expired'   => $q->whereRaw("({$subquery}) < ?", [now()->toDateString()]),
                    '30days'    => $q->whereRaw("({$subquery}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(30)->toDateString()]),
                    '60days'    => $q->whereRaw("({$subquery}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(60)->toDateString()]),
                    '90days'    => $q->whereRaw("({$subquery}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(90)->toDateString()]),
                    '6months'   => $q->whereRaw("({$subquery}) BETWEEN ? AND ?", [now()->toDateString(), now()->addMonths(6)->toDateString()]),
                    default     => null,
                };
            })
            ->when($filters['stock_range'] ?? null, function ($q, $v) {
                $subquery = 'COALESCE((SELECT SUM(quantity_on_hand) FROM stock_batches WHERE product_id = products.id AND status = "active"), 0)';

                match ($v) {
                    'out'      => $q->whereRaw("{$subquery} = 0"),
                    'very_low' => $q->whereRaw("{$subquery} BETWEEN 1 AND 10"),
                    'low'      => $q->whereRaw("{$subquery} BETWEEN 11 AND 30"),
                    'medium'   => $q->whereRaw("{$subquery} BETWEEN 31 AND 100"),
                    'plenty'   => $q->whereRaw("{$subquery} > 100"),
                    default    => null,
                };
            })
            ->when($filters['base_unit'] ?? null, fn ($q, $v) => $q->where('base_unit', $v))
            ->withTotalQuantity()
            ->with('category')
            ->withMin(
                ['stockBatches as nearest_expiry' => fn ($q) => $q->where('status', 'active')->whereNotNull('expiry_date')],
                'expiry_date'
            )
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
