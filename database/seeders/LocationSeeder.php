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

            foreach ($locations as $govName => $cities) {
                $governorate = Governorate::firstOrCreate([
                    'name' => $govName
                ]);
                $citiesData = [];
                foreach ($cities as $cityName) {
                    $citiesData[] = [
                        'governorate_id' => $governorate->id,
                        'name' => $cityName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                City::insert($citiesData);
            }
        });
    }
}
