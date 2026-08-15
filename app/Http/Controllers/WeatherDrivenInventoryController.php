<?php

namespace App\Http\Controllers;

use App\Services\InventoryPredictionService;
use Illuminate\Http\Request;
use Exception;
use Throwable;

class WeatherDrivenInventoryController extends Controller
{
    public function predictInventoryNeeds(Request $request, InventoryPredictionService $predictionService)
    {
        try {
            $pharmacyId = $request->user()->pharmacy_id;

            $result = $predictionService->predictForPharmacy(
                $pharmacyId,
                $request->query('city', 'Damascus'),
                $request->query('governorate', 'Damascus'),
                $request->query('country', 'Syria')
            );

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
