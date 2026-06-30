<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryService->list(
            $request->only(['search',  'per_page'])
        );

        return CategoryResource::collection($categories);
    }
    public function show(Request $request, Category $category): CategoryResource
    {
        $pharmacy = $request->attributes->get('pharmacy');
        $category->loadCount([
            'products' => fn ($q) => $q->where('pharmacy_id', $pharmacy->id)
                ->whereNull('deleted_at'),
        ]);
        return new CategoryResource($category);
    }
}
