<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'pharmacy_id' => 1,
                'category_id' => 1, // Analgesics
                'barcode' => '6281100112233',
                'brand_name' => 'Panadol Extra',
                'scientific_name' => 'Paracetamol / Caffeine',
                'prescription_required' => false,
                'buying_price' => 4500.00,
                'selling_price' => 5500.00,
                'tax_rate' => 0.00,
                'discount_rate' => 0.00,
                'min_stock' => 10,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pharmacy_id' => 1,
                'category_id' => 2, // Antibiotics (تم التصحيح)
                'barcode' => '6281100112244',
                'brand_name' => 'Amoxil 500mg',
                'scientific_name' => 'Amoxicillin',
                'prescription_required' => true,
                'buying_price' => 12000.00,
                'selling_price' => 15000.00,
                'tax_rate' => 0.00,
                'discount_rate' => 0.00,
                'min_stock' => 5,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pharmacy_id' => 1,
                'category_id' => 3, // Vitamins
                'barcode' => '6281100112255',
                'brand_name' => 'Centrum Lutein',
                'scientific_name' => 'Multivitamins / Minerals',
                'prescription_required' => false,
                'buying_price' => 85000.00,
                'selling_price' => 95000.00,
                'tax_rate' => 0.00,
                'discount_rate' => 0.00,
                'min_stock' => 3,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pharmacy_id' => 1,
                'category_id' => 4, // Chronic Diseases
                'barcode' => '6281100112266',
                'brand_name' => 'Concor 5mg',
                'scientific_name' => 'Bisoprolol',
                'prescription_required' => true,
                'buying_price' => 18000.00,
                'selling_price' => 22000.00,
                'tax_rate' => 0.00,
                'discount_rate' => 0.00,
                'min_stock' => 15,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'pharmacy_id' => 1,
                'category_id' => 2, // Antibiotics
                'barcode' => '6281100112277',
                'brand_name' => 'Augmentin 1g',
                'scientific_name' => 'Amoxicillin / Clavulanate Potassium',
                'prescription_required' => true,
                'buying_price' => 45000.00,
                'selling_price' => 52000.00,
                'tax_rate' => 0.00,
                'discount_rate' => 0.00,
                'min_stock' => 8,
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];

        Product::insert($products);
    }
}
