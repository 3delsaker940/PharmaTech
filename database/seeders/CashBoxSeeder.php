<?php

namespace Database\Seeders;

use App\Models\CashBox;
use App\Models\Pharmacy;
use Illuminate\Database\Seeder;

class CashBoxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacy = Pharmacy::first();

        if (! $pharmacy ) {
            $this->command->warn('No pharmacy found. Run DatabaseSeeder first.');
            return;
        }

        CashBox::firstOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
            ],
            [
                'opening_balance' => 2000000.00,
                'current_balance' => 2000000.00,
            ]
        );
    }
}
