<?php

namespace Database\Seeders;

use App\Models\CashBox;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashBoxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        CashBox::firstOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'status'      => 'active',
            ],
            [
                'name'            => 'Main Cash Box',
                'opening_balance' => 2000000.00,
                'current_balance' => 2000000.00,
                'opened_by'       => $owner->id,
                'opened_at'       => now(),
            ]
        );
    }
}
