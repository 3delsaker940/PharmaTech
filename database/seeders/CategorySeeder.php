<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $pharmacyId = 1;

        $categories = [
            [
                'id' => 1,
                'pharmacy_id' => $pharmacyId,
                'name' => 'Analgesics & Antipyretics',
                'description' => 'Medications used to relieve pain and reduce fever, such as Paracetamol and Ibuprofen.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'pharmacy_id' => $pharmacyId,
                'name' => 'Antibiotics',
                'description' => 'Antibacterial medications including capsules, syrups, and injections.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'pharmacy_id' => $pharmacyId,
                'name' => 'Vitamins & Supplements',
                'description' => 'Minerals, general tonics, energy supplements, and bodybuilding formulas.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'pharmacy_id' => $pharmacyId,
                'name' => 'Chronic Disease Medications',
                'description' => 'Medications for hypertension, diabetes, cardiac conditions, and cholesterol.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        Category::insert($categories);
    }
}
