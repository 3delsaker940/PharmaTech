<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $allergy     = Category::where('name', 'Allergy')->first();
        $antibiotics = Category::where('name', 'Antibiotics')->first();
        $vitamins    = Category::where('name', 'Vitamins')->first();
        $painkillers = Category::where('name', 'Painkillers')->first();
        $digestive   = Category::where('name', 'Digestive')->first();
        $dermatology = Category::where('name', 'Dermatology')->first();
        $cardio      = Category::where('name', 'Cardiovascular')->first();
        $diabetes    = Category::where('name', 'Diabetes')->first();

        $box     = Unit::where('name', 'Box')->first();
        $tablet  = Unit::where('name', 'Tablet')->first();
        $capsule = Unit::where('name', 'Capsule')->first();

        $syr  = Company::where('name', 'Syrian Pharma')->first();
        $cham = Company::where('name', 'Cham Pharma')->first();
        $sama = Company::where('name', 'Sama Pharma')->first();

        $now = Carbon::now();

        $products = [
            // ── Core products (keep original barcodes) ─────────────────────────
            [
                'pharmacy_id'           => 1,
                'category_id'           => $allergy->id,
                'company_id'            => $syr?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '10',
                'brand_name'            => 'Panadol Extra',
                'ar_name'               => 'بانادول إكسترا',
                'scientific_name'       => 'Paracetamol / Caffeine',
                'strength'              => '500mg/65mg',
                'prescription_required' => false,
                'buying_price'          => 4500.00,
                'selling_price'         => 5500.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 10,
                'units_per_base'        => 20,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'pharmacy_id'           => 1,
                'category_id'           => $antibiotics->id,
                'company_id'            => $syr?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $capsule?->id,
                'barcode'               => '11',
                'brand_name'            => 'Amoxil 500mg',
                'ar_name'               => 'أموكسيل 500 مغ',
                'scientific_name'       => 'Amoxicillin',
                'strength'              => '500mg',
                'prescription_required' => true,
                'buying_price'          => 12000.00,
                'selling_price'         => 15000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 5,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'pharmacy_id'           => 1,
                'category_id'           => $vitamins->id,
                'company_id'            => $cham?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '12',
                'brand_name'            => 'Centrum Lutein',
                'ar_name'               => 'سنتروم لوتين',
                'scientific_name'       => 'Multivitamins / Minerals',
                'strength'              => null,
                'prescription_required' => false,
                'buying_price'          => 85000.00,
                'selling_price'         => 95000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 3,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'pharmacy_id'           => 1,
                'category_id'           => $painkillers->id,
                'company_id'            => $sama?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '13',
                'brand_name'            => 'Concor 5mg',
                'ar_name'               => 'كونكور 5 مغ',
                'scientific_name'       => 'Bisoprolol',
                'strength'              => '5mg',
                'prescription_required' => true,
                'buying_price'          => 18000.00,
                'selling_price'         => 22000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 15,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'pharmacy_id'           => 1,
                'category_id'           => $antibiotics->id,
                'company_id'            => $sama?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '14',
                'brand_name'            => 'Augmentin 1g',
                'ar_name'               => 'أوغمنتين 1 غ',
                'scientific_name'       => 'Amoxicillin / Clavulanate Potassium',
                'strength'              => '1g',
                'prescription_required' => true,
                'buying_price'          => 45000.00,
                'selling_price'         => 52000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 8,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // ── Additional products for richer reports ─────────────────────────

            // Digestive — high margin, sells well → Top Products & Profit variety
            [
                'pharmacy_id'           => 1,
                'category_id'           => $digestive?->id ?? $antibiotics->id,
                'company_id'            => $cham?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $capsule?->id,
                'barcode'               => '15',
                'brand_name'            => 'Omeprazole 20mg',
                'ar_name'               => 'أوميبرازول 20 مغ',
                'scientific_name'       => 'Omeprazole',
                'strength'              => '20mg',
                'prescription_required' => false,
                'buying_price'          => 8000.00,
                'selling_price'         => 13000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 10,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // Dermatology — medium margin, moderate sales
            [
                'pharmacy_id'           => 1,
                'category_id'           => $dermatology?->id ?? $vitamins->id,
                'company_id'            => $syr?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '16',
                'brand_name'            => 'Betadine 500ml',
                'ar_name'               => 'بيتادين 500 مل',
                'scientific_name'       => 'Povidone-Iodine',
                'strength'              => '10%',
                'prescription_required' => false,
                'buying_price'          => 22000.00,
                'selling_price'         => 30000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 5,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // Cardiovascular — expensive, low sales → will become near-dead stock
            [
                'pharmacy_id'           => 1,
                'category_id'           => $cardio?->id ?? $painkillers->id,
                'company_id'            => $sama?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '17',
                'brand_name'            => 'Crestor 10mg',
                'ar_name'               => 'كريستور 10 مغ',
                'scientific_name'       => 'Rosuvastatin',
                'strength'              => '10mg',
                'prescription_required' => true,
                'buying_price'          => 65000.00,
                'selling_price'         => 78000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 5,
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // Diabetes — low stock scenario (min_stock=10, will seed only 2 units)
            [
                'pharmacy_id'           => 1,
                'category_id'           => $diabetes?->id ?? $antibiotics->id,
                'company_id'            => $cham?->id,
                'base_unit_id'          => $box?->id,
                'selling_unit_id'       => $tablet?->id,
                'barcode'               => '18',
                'brand_name'            => 'Glucophage 1000mg',
                'ar_name'               => 'غلوكوفاج 1000 مغ',
                'scientific_name'       => 'Metformin',
                'strength'              => '1000mg',
                'prescription_required' => true,
                'buying_price'          => 15000.00,
                'selling_price'         => 20000.00,
                'tax_rate'              => 0.00,
                'discount_rate'         => 0.00,
                'min_stock'             => 10,   // high threshold → will show as low-stock
                'units_per_base'        => 1,
                'allow_partial_selling' => false,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ];

        Product::insert($products);
    }
}
