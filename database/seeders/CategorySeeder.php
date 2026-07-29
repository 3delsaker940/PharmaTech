<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Antibiotics',
                'description' => 'Antibiotics',
            ],
            [
                'name'        => 'Painkillers',
                'description' => 'Painkillers',
            ],
            [
                'name'        => 'Digestive',
                'description' => 'Digestive',
            ],
            [
                'name'        => 'Diabetes',
                'description' => 'Diabetes',
            ],
            [
                'name'        => 'Cardiovascular',
                'description' => 'Cardiovascular',
            ],
            [
                'name'        => 'Vitamins',
                'description' => 'Vitamins',
            ],
            [
                'name'        => 'Hormones',
                'description' => 'Hormones',
            ],
            [
                'name'        => 'Dermatology',
                'description' => 'Dermatology',
            ],
            [
                'name'        => 'Allergy',
                'description' => 'Allergy',
            ],
            [
                'name'        => 'Antifungals',
                'description' => 'Antifungals',
            ],
            [
                'name'        => 'Others',
                'description' => 'Products that do not fit any other category.',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
