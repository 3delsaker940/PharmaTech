<?php

namespace App\Services;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    private function getDefaults(): array
    {
        return [
            'prescription_required' => false,
            'tax_rate' => 0.0,
            'discount_rate' => 0.0,
            'min_stock' => 0,
            'units_per_base' => 1,
            'allow_partial_selling' => false,
        ];
    }
    private function sanitizeForStore(array $data): array
    {
        $data = array_merge($this->getDefaults(), $data);
        foreach ($this->getDefaults() as $key => $default) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = $default;
            }
        }
        if (empty($data['base_unit_id'])) {
            $piece = Unit::where('name', 'Piece')->first();
            $data['base_unit_id'] = $piece?->id;
        }
        if (empty($data['selling_unit_id'])) {
            $piece = $piece ?? Unit::where('name', 'Piece')->first();
            $data['selling_unit_id'] = $piece?->id;
        }
        return $data;
    }
    private function sanitizeForUpdate(array $data): array
    {
        $defaults = $this->getDefaults();
        foreach ($defaults as $key => $default) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = $default;
            }
        }
        if (array_key_exists('base_unit_id', $data) && $data['base_unit_id'] === null) {
            $piece = Unit::where('name', 'Piece')->first();
            $data['base_unit_id'] = $piece?->id;
        }
        if (array_key_exists('selling_unit_id', $data) && $data['selling_unit_id'] === null) {
            $piece = $piece ?? Unit::where('name', 'Piece')->first();
            $data['selling_unit_id'] = $piece?->id;
        }
        return $data;
    }
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
                        ->orWhere('barcode', 'like', $term)
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
                filled($filters['company_id'] ?? null),
                fn ($q) => $q->whereIn(
                    'company_id',
                    is_array($filters['company_id'])
                        ? $filters['company_id']
                        : array_filter(array_map('trim', explode(',', $filters['company_id'])))
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
                    'stock_asc'  => $q->orderBy('total_quantity_sum', 'asc'),
                    'stock_desc' => $q->orderBy('total_quantity_sum', 'desc'),
                    'expiry_asc' => $q->orderBy('nearest_expiry', 'asc'),
                    'expiry_desc' => $q->orderBy('nearest_expiry', 'desc'),
                    default => $q->orderByDesc('id'),
                };
            })
           ->when(! isset($filters['sort_by']), fn ($q) => $q->orderBy('brand_name', 'asc'))
            ->when($filters['stock_status'] ?? null, function ($q, $v) {
                match ($v) {
                    'out' => $q->whereDoesntHave(
                        'stockBatches',
                        fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0)
                    ),
                    'low' => $q->whereHas(
                        'stockBatches',
                        fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0)
                    )
                        ->where('min_stock', '>', $this->activeStockSubquery()),
                    'available' => $q->where('min_stock', '<=', $this->activeStockSubquery()),
                    default => null,
                };
            })
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('selling_price', '<=', $v))
            ->when(filled($filters['expiry_filter'] ?? null), function ($q) use ($filters) {
                $values = is_array($filters['expiry_filter'])
                    ? $filters['expiry_filter']
                    : array_filter(array_map('trim', explode(',', $filters['expiry_filter'])));

                if (empty($values)) {
                    return;
                }

                $today = now()->toDateString();

                $ranges = [
                    '30days'  => [$today, now()->addDays(30)->toDateString()],
                    '60days'  => [$today, now()->addDays(60)->toDateString()],
                    '90days'  => [$today, now()->addDays(90)->toDateString()],
                    '6months' => [$today, now()->addMonths(6)->toDateString()],
                ];

                $q->having(function ($having) use ($values, $ranges, $today) {
                    $first = true;

                    foreach ($values as $value) {
                        $value = trim($value);

                        if ($value === 'expired') {
                            $first
                                ? $having->having('nearest_expiry', '<', $today)
                                : $having->orHaving('nearest_expiry', '<', $today);
                            $first = false;
                            continue;
                        }

                        if (! array_key_exists($value, $ranges)) {
                            continue;
                        }

                        [$start, $end] = $ranges[$value];

                        $first
                            ? $having->havingBetween('nearest_expiry', [$start, $end])
                            : $having->orHavingBetween('nearest_expiry', [$start, $end]);

                        $first = false;
                    }
                });
            })
            ->when($filters['stock_range'] ?? null, function ($q, $v) {
                match ($v) {
                    'out' => $q->whereDoesntHave(
                        'stockBatches',
                        fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0)
                    ),
                    'very_low' => $q->having('total_quantity_sum', '>=', 1)->having('total_quantity_sum', '<=', 10),
                    'low' => $q->having('total_quantity_sum', '>=', 11)->having('total_quantity_sum', '<=', 30),
                    'medium' => $q->having('total_quantity_sum', '>=', 31)->having('total_quantity_sum', '<=', 100),
                    'plenty' => $q->having('total_quantity_sum', '>', 100),
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
            ->withSum(
                ['stockBatches as total_quantity_sum' => fn ($q) => $q->where('status', 'active')],
                'quantity_on_hand'
            )
            //->withTotalQuantity()
            ->with('category', 'company', 'baseUnit', 'sellingUnit')
            ->withMin(
                ['stockBatches as nearest_expiry' => fn ($q) => $q->where('status', 'active')->whereNotNull('expiry_date')],
                'expiry_date'
            )
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
    private function activeStockSubquery(float $multiplier = 1): \Illuminate\Database\Query\Builder
    {
        return DB::table('stock_batches')
            ->selectRaw("COALESCE(SUM(quantity_on_hand), 0) * {$multiplier}")
            ->whereColumn('stock_batches.product_id', 'products.id')
            ->where('stock_batches.status', 'active');
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
        $sanitized = $this->sanitizeForStore($data);
        return Product::create([
            ...$sanitized,
            'pharmacy_id' => $pharmacy->id,
        ]);
    }

    public function update(Product $product, array $data): Product
    {
        $sanitized = $this->sanitizeForUpdate($data);
        $product->update($sanitized);

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
