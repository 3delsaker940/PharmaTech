<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Box',         'type' => 'packaging'],
            ['name' => 'Bottle',      'type' => 'packaging'],
            ['name' => 'Vial',        'type' => 'packaging'],
            ['name' => 'Ampoule',     'type' => 'packaging'],
            ['name' => 'Tube',        'type' => 'packaging'],
            ['name' => 'Sachet',      'type' => 'packaging'],
            ['name' => 'Syringe',     'type' => 'packaging'],
            ['name' => 'Inhaler',     'type' => 'packaging'],
            ['name' => 'Patch',       'type' => 'packaging'],
            ['name' => 'Pack',        'type' => 'packaging'],
            ['name' => 'Can',         'type' => 'packaging'],
            ['name' => 'Pouch',       'type' => 'packaging'],

            ['name' => 'Tablet',      'type' => 'unit'],
            ['name' => 'Capsule',     'type' => 'unit'],
            ['name' => 'Strip',       'type' => 'unit'],
            ['name' => 'Piece',       'type' => 'unit'],
            ['name' => 'Ml',          'type' => 'unit'],
            ['name' => 'Mg',          'type' => 'unit'],
            ['name' => 'Drop',        'type' => 'unit'],
            ['name' => 'Suppository', 'type' => 'unit'],
            ['name' => 'Gram',        'type' => 'unit'],
            ['name' => 'Liter',       'type' => 'unit'],
            ['name' => 'IU',          'type' => 'unit'],
            ['name' => 'Dose',        'type' => 'unit'],
            ['name' => 'Application', 'type' => 'unit'],
            ['name' => 'Spray',       'type' => 'unit'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
