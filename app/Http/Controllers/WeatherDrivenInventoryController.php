<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WeatherService;
use App\Services\LLMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Exception;

class WeatherDrivenInventoryController extends Controller
{
    public function predictInventoryNeeds(Request $request, WeatherService $weatherService, LLMService $llmService)
    {
        try {
            // 1. Isolate data by getting the current user's pharmacy_id
            $pharmacyId = auth()->user()->pharmacy_id;

            // 2. Define a unique Cache key for this pharmacy for today
            $cacheKey = "ai_forecast_pharmacy_{$pharmacyId}_" . now()->format('Y-m-d');

            // 3. Check if we already have a cached response for today
            if (Cache::has($cacheKey)) {
                return response()->json(Cache::get($cacheKey));
            }

            // 4. Get location inputs
            $city        = $request->query('city', 'Damascus');
            $governorate = $request->query('governorate', 'Damascus');
            $country     = $request->query('country', 'Syria');

            // 5. Fetch weather data
            $weatherResponse = $weatherService->getWeatherForecast($city, $governorate, $country);

            if (!$weatherResponse['success']) {
                throw new Exception("Failed to fetch weather data.");
            }

            $weatherData = $weatherResponse['data'];

            // 6. Fetch ALL products for this pharmacy and sum their stock
            $inventoryData = Product::with(['category', 'medicalInfo'])
                ->where('pharmacy_id', $pharmacyId)
                ->withSum('stockBatches', 'quantity_on_hand')
                ->get()
                ->map(function ($product) {
                    return [
                        'name'     => $product->brand_name ?? $product->scientific_name,
                        'stock'    => $product->stock_batches_sum_quantity_on_hand ?? 0,
                        'min'      => $product->min_stock,
                    ];
                })->toArray();

            // 7. English Prompt Engineering (Requesting JSON output)
            $prompt = "You are a pharmacy management expert. The expected weather in {$city} is: " . json_encode($weatherData) . "\n";
            $prompt .= "Here is the current inventory of the pharmacy: " . json_encode($inventoryData) . "\n";
            $prompt .= "Analyze the weather and inventory. Provide 2 to 3 actionable recommendations on which medicines to restock based on weather conditions (e.g., allergy meds for dust, cold meds for rain). \n";
            $prompt .= "You MUST respond ONLY in valid JSON format like this:\n";
            $prompt .= "[\n  {\"product_name\": \"Medicine Name\", \"advice\": \"Reason and suggested action\"}\n]";

            // 8. Get AI Analysis
            $aiAnalysisString = $llmService->generateContent($prompt);

            // 9. Clean up potential markdown formatting from Gemini
            $aiAnalysisString = str_replace(['```json', '```'], '', $aiAnalysisString);
            $aiAnalysisJson = json_decode(trim($aiAnalysisString), true);

            $finalResponse = [
                'status' => 'success',
                'weather_summary' => $weatherData['current']['condition'] ?? 'Unknown',
                'ai_recommendations' => $aiAnalysisJson ?? $aiAnalysisString
            ];

            // 10. Store the result in the Cache until the end of the day
            Cache::put($cacheKey, $finalResponse, now()->endOfDay());

            return response()->json($finalResponse);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
