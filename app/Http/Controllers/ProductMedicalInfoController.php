<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertProductMedicalInfoRequest;
use App\Http\Resources\ProductMedicalInfoResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductMedicalInfoController extends Controller
{
    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $medicalInfo = $product->medicalInfo;

        if (! $medicalInfo) {
            return response()->json(['data' => null], 200);
        }

        return (new ProductMedicalInfoResource($medicalInfo))->response();
    }

    public function upsert(UpsertProductMedicalInfoRequest $request, Product $product): JsonResponse
    {
        // Ownership already verified in UpsertProductMedicalInfoRequest::authorize()

        $medicalInfo = $product->medicalInfo()->updateOrCreate(
            ['product_id' => $product->id],
            $request->validated()
        );

        $statusCode = $medicalInfo->wasRecentlyCreated ? 201 : 200;

        return (new ProductMedicalInfoResource($medicalInfo))
            ->response()
            ->setStatusCode($statusCode);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->medicalInfo()?->delete();

        return response()->json([
            'message' => 'Medical info deleted successfully.',
        ], 200);
    }
}
