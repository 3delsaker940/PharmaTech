<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'location' => [
                'city'    => $this['location']['name'] ?? null,
                'region'  => $this['location']['region'] ?? null,
                'country' => $this['location']['country'] ?? null,
            ],
            'current' => [
                'temp_c'       => $this['current']['temp_c'] ?? null,
                'feels_like_c' => $this['current']['feelslike_c'] ?? null,
                'condition'    => $this['current']['condition']['text'] ?? null,
                'humidity'     => $this['current']['humidity'] ?? null,
                'wind_kph'     => $this['current']['wind_kph'] ?? null,
                'is_day'       => $this['current']['is_day'] ?? null,
            ],
            // توقعات الأسبوع (7 أيام)
            'weekly_forecast' => collect($this['forecast']['forecastday'] ?? [])->map(function ($day) {
                return [
                    'date'           => $day['date'] ?? null,
                    'max_temp_c'     => $day['day']['maxtemp_c'] ?? null,
                    'min_temp_c'     => $day['day']['mintemp_c'] ?? null,
                    'condition'      => $day['day']['condition']['text'] ?? null,
                    'chance_of_rain' => $day['day']['daily_chance_of_rain'] ?? null,
                    'max_wind_kph'   => $day['day']['maxwind_kph'] ?? null,
                    'avg_humidity'   => $day['day']['avghumidity'] ?? null,
                ];
            })->toArray(),
        ];
    }
}
