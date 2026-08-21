<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Pharmacy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacy = Pharmacy::first();

        if (! $pharmacy) {
            $this->command->warn('No pharmacy found. Run DatabaseSeeder first.');
            return;
        }

        $customers = [
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'Hasan',
                'phone' => '+963911111111',
                'notes' => null,
            ],
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'joao',
                'phone' => '+963922222222',
                'notes' => 'Regular customer',
            ],
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'omid',
                'phone' => null,
                'notes' => null,
            ],
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'Layla',
                'phone' => '+963933333333',
                'notes' => 'Regular customer — buys vitamins monthly',
            ],
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'Karim',
                'phone' => '+963944444444',
                'notes' => null,
            ],
            [
                'pharmacy_id' => $pharmacy->id,
                'full_name' => 'Sara',
                'phone' => null,
                'notes' => 'Walk-in, prefers cash payment',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                [
                    'pharmacy_id' => $customer['pharmacy_id'],
                    'full_name' => $customer['full_name'],
                ],
                $customer
            );
        }
    }
}
