<?php

namespace App\Console\Commands;

use App\Models\Pharmacy;
use App\Services\InventoryPredictionService;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:analyze-stock-with-weather')]
#[Description('Analyze stock levels in relation to weather patterns and send advices notifications to pharmacy owners')]
class AnalyzeStockWithWeather extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InventoryPredictionService $predictionService, NotificationService $notifier)
    {
        $pharmacies = Pharmacy::with('owner')->get();

        foreach ($pharmacies as $pharmacy) {
            if (!$pharmacy->owner) continue;

            try {
                $report = $predictionService->predictForPharmacy($pharmacy->id);

                // تجميع النص من الـ Array للإشعار
                $adviceText = collect($report['ai_recommendations'])
                    ->map(fn($item) => "{$item['product_name']}: {$item['advice']}")
                    ->implode("\n");

                $notifier->sendAndSave(
                    $pharmacy->owner,
                    'AI Weather & Stock Report ☁️📦',
                    "Daily Recommendations:\n" . $adviceText,
                    ['pharmacy_id' => $pharmacy->id]
                );
            } catch (\Exception $e) {
                Log::error("AI Report failed for Pharmacy {$pharmacy->id}: " . $e->getMessage());
            }
        }
    }
}
