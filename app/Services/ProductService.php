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
                        ->orWhere('ar_name', 'like', $term)
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $term));
                })
            )
            ->when(
                filled($filters['category_id'] ?? null),
                fn ($q) => $q->whereIn(
                    'category_id',
                    is_array($filters['category_id'])
                        ? $filters['category_id']
                        : array_filter(array_map('trim', explode(',', $filters['category_id'])))
                )
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
            ->when(
                filled($filters['expiry_filter'] ?? null),
                function ($q) use ($filters) {
                    $values = is_array($filters['expiry_filter'])
                        ? $filters['expiry_filter']
                        : array_filter(array_map('trim', explode(',', $filters['expiry_filter'])));
                    if (empty($values)) {
                        return;
                    }
                    $sub = 'SELECT MIN(expiry_date) FROM stock_batches WHERE product_id = products.id AND status = "active" AND expiry_date IS NOT NULL';
                    $q->where(function ($inner) use ($values, $sub) {
                        foreach ($values as $index => $v) {
                            $condition = match (trim($v)) {
                                'expired' => ["({$sub}) < ?", [now()->toDateString()]],
                                '30days'  => ["({$sub}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(30)->toDateString()]],
                                '60days'  => ["({$sub}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(60)->toDateString()]],
                                '90days'  => ["({$sub}) BETWEEN ? AND ?", [now()->toDateString(), now()->addDays(90)->toDateString()]],
                                '6months' => ["({$sub}) BETWEEN ? AND ?", [now()->toDateString(), now()->addMonths(6)->toDateString()]],
                                default   => null,
                            };
                            if ($condition === null) {
                                continue;
                            }
                            [$sql, $bindings] = $condition;
                            $index === 0
                                ? $inner->whereRaw($sql, $bindings)
                                : $inner->orWhereRaw($sql, $bindings);
                        }
                    });
                }
            )
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
            ->when(
                filled($filters['base_unit_id'] ?? null),
                fn ($q) => $q->whereIn(
                    'base_unit_id',
                    is_array($filters['base_unit_id'])
                        ? $filters['base_unit_id']
                        : array_filter(array_map('trim', explode(',', $filters['base_unit_id'])))
                )
            )
            ->when(
                $filters['in_stock'] ?? null,
                fn ($q) => $q->whereHas('stockBatches', fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0))
            )
            ->withTotalQuantity()
            ->with('category', 'company', 'baseUnit', 'sellingUnit')
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
            'category_id', 'barcode', 'brand_name', 'scientific_name', 'strength',
            'prescription_required', 'buying_price', 'selling_price', 'ar_name', 'company_id',
            'tax_rate', 'discount_rate', 'min_stock', 'max_stock', 'image_path', 'shelf',
            'base_unit_id', 'selling_unit_id', 'units_per_base', 'allow_partial_selling',
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
