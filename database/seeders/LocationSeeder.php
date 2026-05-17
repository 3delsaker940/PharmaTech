<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Governorate;
use App\Models\City;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = require database_path('data/locations.php');

        DB::transaction(function () use ($locations) {

            foreach ($locations as $govKey => $citiesKeys) {

                $govNameEn = __("locations.{$govKey}", [], 'en');
                $govNameAr = __("locations.{$govKey}", [], 'ar');

                $governorate = Governorate::where('name->en', $govNameEn)->first();
                if (!$governorate) {
                    $governorate = Governorate::create([
                        'name' => [
                            'en' => $govNameEn,
                            'ar' => $govNameAr,
                        ]
                    ]);
                }

                $citiesData = [];
                foreach ($citiesKeys as $cityKey) {

                    $cityNameEn = __("locations.{$cityKey}", [], 'en');
                    $cityNameAr = __("locations.{$cityKey}", [], 'ar');

                    $citiesData[] = [
                        'governorate_id' => $governorate->id,
                        'name' => json_encode([
                            'en' => $cityNameEn,
                            'ar' => $cityNameAr,
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                City::insert($citiesData);
            }
        });
    }
}
