<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockBatchResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    use AuthorizesPharmacyResource;

    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->productService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['search', 'category_id', 'prescription_required', 'with_trashed', 'per_page'])
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->store(
            $request->attributes->get('pharmacy'),
            $request->validated()
        );

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        return new ProductResource($product->load(['category', 'medicalInfo']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $this->authorizePharmacyResource($request, $product->pharmacy_id);

        $updated = $this->productService->update($product, $request->validated());

        return new ProductResource($updated->load(['category', 'medicalInfo']));
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

        $products = Product::where('pharmacy_id', $pharmacy->id)
            ->whereNull('deleted_at')
            ->withSum(
                ['stockBatches as total_quantity_sum' => fn ($q) => $q->where('status', 'active')],
                'quantity_on_hand'
            )
            ->havingRaw('COALESCE(total_quantity_sum, 0) < min_stock')
            ->with('category')
            ->orderByRaw('COALESCE(total_quantity_sum, 0) ASC')
            ->paginate((int) $request->input('per_page', 15));

        return ProductResource::collection($products);
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
