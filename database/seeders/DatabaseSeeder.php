<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => '3delsaker940@gmail.com'],
            [
                'first_name' => 'Adel',
                'father_name' => 'Giath',
                'last_name' => 'Saker',
                'phone_number' => '0935542610',
                'licence_number' => '188235499',
                'password' => Hash::make('Password123'),
                'status' => 'active',
            ]
        );

        Pharmacy::firstOrCreate(
            ['id' => 1],
            [
                'user_id' => $user->id,
                'name' => 'Soso Pharma',
                'city_id' => 1,
                'address' => 'next to alakram mosque',
                'phone_number' => '0935542610',
                'license_number' => '188235499',
                'status' => 'active',
            ]
        );

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            StockBatchSeeder::class,
        ]);
    }
}
