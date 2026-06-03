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
            $request->only(['search', 'status', 'per_page'])
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

    public function deactivate(Request $request, Category $category): CategoryResource
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        $updated = $this->categoryService->deactivate($category);

        return new CategoryResource($updated);
    }

    public function activate(Request $request, Category $category): CategoryResource
    {
        $this->authorizePharmacyResource($request, $category->pharmacy_id);

        $updated = $this->categoryService->activate($category);

        return new CategoryResource($updated);
    }
}
