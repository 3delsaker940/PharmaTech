<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesPharmacyResource;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    use AuthorizesPharmacyResource;
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryService->list(
            $request->attributes->get('pharmacy'),
            $request->only(['search', 'with_trashed', 'per_page'])
        );

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->store(
            $request->attributes->get('pharmacy'),
            $request->validated()
        );

        return (new CategoryResource($category->loadCount('products')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        $updated = $this->categoryService->update($category, $request->validated());

        return new CategoryResource($updated->loadCount('products'));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        $this->categoryService->delete($category);

        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }

    public function restore(Request $request, Category $category): CategoryResource
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        $restored = $this->categoryService->restore($category);

        return new CategoryResource($restored->loadCount('products'));
    }
}
