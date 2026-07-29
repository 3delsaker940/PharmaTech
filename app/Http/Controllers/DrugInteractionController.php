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

        $scientificNames = Product::whereIn('id', $productIds)
            ->pluck('scientific_name')
            ->toArray();

        $prompt = "You are a clinical pharmacist. Check for drug-drug interactions between these active ingredients: " . implode(', ', $scientificNames) . ". \n";
        $prompt .= "Respond ONLY in valid JSON format like this:\n";
        $prompt .= "[{\"severity\": \"High or Moderate or Low\", \"interaction\": \"brief explanation\"}]\n";
        $prompt .= "If there are no interactions, return an empty array [].";

        try {
            $aiResponse = $llmService->generateContent($prompt);
            $cleanJson = json_decode(trim(str_replace(['```json', '```'], '', $aiResponse)), true);

            return response()->json([
                'status' => 'success',
                'interactions' => $cleanJson
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check interactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
