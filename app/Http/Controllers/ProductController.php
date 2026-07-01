<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockBatchResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use AuthorizesPharmacyResource;

    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->productService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['search',
                'category_id',
                'company_id',
                'prescription_required',
                'with_trashed',
                'per_page',
                'sort_by',
                'stock_status',
                'min_price',
                'max_price',
                'expiry_filter',
                'stock_range',
                'base_unit',
                'in_stock',])
        );

        return ProductCardResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->store(
            $request->attributes->get('pharmacy'),
            $request->validated()
        );

        return (new ProductResource($product->load('category' ,'company', 'baseUnit', 'sellingUnit')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        return new ProductResource($product->load(['category', 'company', 'baseUnit', 'sellingUnit', 'medicalInfo']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        $updated = $this->productService->update($product, $request->validated());

        return new ProductResource($updated->load(['category', 'company', 'baseUnit', 'sellingUnit', 'medicalInfo']));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted successfully.'], 200);
    }

    public function restore(Request $request, Product $product): ProductResource
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        $restored = $this->productService->restore($product);

        return new ProductResource($restored);
    }

    public function lookupByBarcode(Request $request, string $barcode): JsonResponse
    {
        $product = $this->productService->findByBarcode(
            $request->attributes->get('pharmacy'),
            $barcode
        );

        if (! $product) {
            return response()->json([
                'message' => 'No product found for barcode: ' . $barcode,
            ], 404);
        }

        return (new ProductResource($product))->response();
    }
    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $pharmacy = $request->attributes->get('pharmacy');
        $activeStock = fn (float $multiplier = 1) => DB::table('stock_batches')
            ->selectRaw("COALESCE(SUM(quantity_on_hand), 0) * {$multiplier}")
            ->whereColumn('stock_batches.product_id', 'products.id')
            ->where('stock_batches.status', 'active');

        $products = Product::where('pharmacy_id', $pharmacy->id)
            ->where('min_stock', '>', $activeStock())
            ->withSum(
                ['stockBatches as total_quantity_sum' => fn ($q) => $q->where('status', 'active')],
                'quantity_on_hand'
            )
            ->withMin(
                ['stockBatches as nearest_expiry' => fn ($q) => $q->where('status', 'active')->whereNotNull('expiry_date')],
                'expiry_date'
            )
            ->with('category')
            ->when($request->filled('severity'), function ($q) use ($request, $activeStock) {
                match ($request->input('severity')) {
                    'out' => $q->whereDoesntHave(
                        'stockBatches',
                        fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0)
                    ),
                    'critical' => $q->whereHas(
                        'stockBatches',
                        fn ($b) => $b->where('status', 'active')->where('quantity_on_hand', '>', 0)
                    )
                        ->where('min_stock', '>=', $activeStock(4)),
                    'low' => $q->where('min_stock', '<', $activeStock(4))
                        ->where('min_stock', '>', $activeStock()),
                    default => null,
                };
            })
            ->orderBy('total_quantity_sum', 'asc')
            ->paginate((int) $request->input('per_page', 15));

        return ProductCardResource::collection($products);
    }

    public function availableBatches(Request $request, Product $product): AnonymousResourceCollection
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        $batches = $product->stockBatches()
            ->where('status', 'active')
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('received_at')
            ->paginate((int) $request->input('per_page', 15));

        return StockBatchResource::collection($batches);
    }
}
