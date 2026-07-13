<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function header(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getHeader(
            $request->attributes->get('pharmacy')
        );
        return response()->json(['data' => $data], 200);
    }
    public function cards(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getCards(
            $request->attributes->get('pharmacy')
        );
        return response()->json(['data' => $data], 200);
    }
    public function weeklyRevenue(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getWeeklyRevenue(
            $request->attributes->get('pharmacy')
        );
        return response()->json(['data' => $data], 200);
    }
    public function transactions(Request $request): JsonResponse
    {
        $results = $this->dashboardService->getTransactions(
            $request->attributes->get('pharmacy'),
            $request->only(['type', 'per_page'])
        );
        return response()->json($results, 200);
    }
}
