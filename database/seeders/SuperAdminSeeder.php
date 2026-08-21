<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'system_admin', 'guard_name' => 'web']);

        $user = User::findByEmail('admin@pharmatech.com');
        if (! $user) {
            $user = User::create([
                'email' => 'admin@pharmatech.com',
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'password'   => Hash::make('password321'),
                'status'     => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $user->assignRole($role);
    }
}
