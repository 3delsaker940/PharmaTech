<?php

namespace App\Services;

use App\Http\Resources\WeatherSummaryResource;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function getWeatherForecast(string $city = 'Douma', string $governorate = 'Rif Dimashq', string $country = 'Syria', int $days = 7): array
    {
        $locationQuery = "{$city}, {$governorate}, {$country}";

        $apiKey = config('services.weather.key', env('WEATHER_API_KEY'));

        $response = Http::get("http://api.weatherapi.com/v1/forecast.json", [
            'key'  => $apiKey,
            'q'    => $locationQuery,
            'days' => $days,
        ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'status'  => $response->status(),
                'details' => $response->json(),
            ];
        }

        $filteredData = (new WeatherSummaryResource($response->json()))->resolve();

        return [
            'success' => true,
            'data'    => $filteredData,
        ];
    }
}
