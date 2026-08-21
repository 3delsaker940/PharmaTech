<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            ['name' => 'Syrian Pharma', 'address' => 'Damascus, Syria'],
            ['name' => 'Sama Pharma', 'address' => 'Damascus, Syria'],
            ['name' => 'Cham Pharma', 'address' => 'Damascus, Syria'],
            ['name' => 'Hama Pharma', 'address' => 'Hama, Syria'],
            ['name' => 'Averroes Pharma', 'address' => 'Aleppo, Syria'],
            ['name' => 'Unipharma', 'address' => 'Homs, Syria'],
            ['name' => 'Barakat Pharma', 'address' => 'Latakia, Syria'],
        ];
        foreach ($companies as $company) {
            Company::firstOrCreate(['name' => $company['name']], $company);
        }
    }
}
