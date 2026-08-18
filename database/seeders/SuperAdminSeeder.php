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
        // 1. تأكد إنه الـ role موجود (أو اعمله)
        $role = Role::firstOrCreate(['name' => 'system_admin', 'guard_name' => 'web']);

        // 2. اعمل المستخدم
        $user = User::firstOrCreate(
            ['email' => 'admin@pharmatech.com'], // غيّرها للإيميل يلي بدك ياه
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'password'   => Hash::make('Password321'), // غيّرها لباسورد قوي
                'status'     => 'active',
                'email_verified_at' => now(),
                // pharmacy_id بيضل null لأنه super admin مش تابع لصيدلية معينة
            ]
        );

        // 3. أعطيه الـ role
        $user->assignRole($role);
    }
}
