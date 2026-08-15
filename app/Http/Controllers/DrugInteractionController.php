<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\LLMService;
use App\Http\Requests\CheckDrugInteractionRequest;

class DrugInteractionController extends Controller
{
    public function checkInteractions(CheckDrugInteractionRequest $request, LLMService $llmService)
    {
        $productIds = $request->validated('product_ids');

        $products = Product::whereIn('id', $productIds)
            ->get(['id', 'brand_name', 'scientific_name']);

        // Use scientific name when available, fall back to brand name.
        $drugNames = $products->map(function ($p) {
            return $p->scientific_name ?: $p->brand_name;
        })->filter()->values()->toArray();

        if (count($drugNames) < 2) {
            return response()->json([
                'status'  => 'error',
                'message' => 'At least two drugs with identifiable names are required.',
            ], 422);
        }

        $prompt = "You are a clinical pharmacist. Check for drug-drug interactions between these active ingredients: " . implode(', ', $drugNames) . ".\n";
        $prompt .= "Respond ONLY in valid JSON format like this:\n";
        $prompt .= "[{\"severity\": \"High or Moderate or Low\", \"interaction\": \"brief explanation\"}]\n";
        $prompt .= "If there are no interactions, return an empty array [].";

        try {
            $aiResponse = $llmService->generateContent($prompt);
            $cleanJson = json_decode(
                trim(str_replace(["```json", "```"], "", $aiResponse)),
                true
            );

            return response()->json([
                'status'       => 'success',
                'interactions' => is_array($cleanJson) ? $cleanJson : [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to check interactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
