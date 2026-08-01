<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Exception;

class InventoryPredictionService
{
    public function __construct(
        protected WeatherService $weatherService,
        protected LLMService $llmService
    ) {}

    public function predictForPharmacy($pharmacyId, $city = 'Damascus', $governorate = 'Damascus', $country = 'Syria')
    {
        $cacheKey = "ai_forecast_pharmacy_{$pharmacyId}_" . now()->format('Y-m-d');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $weatherResponse = $this->weatherService->getWeatherForecast($city, $governorate, $country);
        if (!$weatherResponse['success']) {
            throw new Exception("Failed to fetch weather data.");
        }

        $inventoryData = Product::with(['category', 'medicalInfo'])
            ->where('pharmacy_id', $pharmacyId)
            ->withSum('stockBatches', 'quantity_on_hand')
            ->get()
            ->map(fn($product) => [
                'name'  => $product->brand_name ?? $product->scientific_name,
                'stock' => $product->stock_batches_sum_quantity_on_hand ?? 0,
                'min'   => $product->min_stock,
            ])->toArray();

        $prompt = "You are a pharmacy management expert. The expected weather in {$city} is: " . json_encode($weatherResponse['data']) . "\n";
        $prompt .= "Here is the current inventory: " . json_encode($inventoryData) . "\n";
        $prompt .= "Provide 2 to 3 actionable recommendations on which medicines to restock based on weather conditions.\n";
        $prompt .= "You MUST respond ONLY in valid JSON format like this:\n";
        $prompt .= "[\n  {\"product_name\": \"Medicine Name\", \"advice\": \"Reason and suggested action\"}\n]";

        $aiAnalysisString = $this->llmService->generateContent($prompt);
        $aiAnalysisJson = json_decode(str_replace(['```json', '```'], '', trim($aiAnalysisString)), true);

        $result = [
            'status' => 'success',
            'weather_summary' => $weatherResponse['data']['current']['condition'] ?? 'Unknown',
            'ai_recommendations' => $aiAnalysisJson ?? $aiAnalysisString
        ];

        Cache::put($cacheKey, $result, now()->endOfDay());

        return $result;
    }
}
