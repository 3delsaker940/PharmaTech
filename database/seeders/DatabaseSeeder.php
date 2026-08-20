<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // NOTE: WithoutModelEvents was removed on purpose. User/Customer/Supplier
    // now compute their *_hash columns inside a `saving` model event
    // (see App\Models\User::booted(), etc). Disabling model events here
    // would silently skip that computation and leave rows unfindable by
    // findByEmail()/findByPhone() (and break login for the seeded user).

    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            RolesAndPermissionsSeeder::class,
            LocationSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            CompanySeeder::class,
        ]);

        $pharmacy = Pharmacy::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Soso Pharma',
                'city_id' => 1,
                'address' => 'next to alakram mosque',
                'phone_number' => '0935542610',
                'license_number' => '188235499',
                'status' => 'active',
            ]
        );

        $user = User::findByEmail('3delsaker940@gmail.com');
        if (! $user) {
            $user = User::create([
                'email' => '3delsaker940@gmail.com',
                'first_name' => 'Adel',
                'father_name' => 'Giath',
                'last_name' => 'Saker',
                'phone_number' => '0935542610',
                'password' => Hash::make('Password123'),
                'status' => 'active',
                'pharmacy_id' => $pharmacy->id,
            ]);
        }
        $user->assignRole('pharmacy_owner');
        $user->markEmailAsVerified();

        $this->call([
            ProductSeeder::class,
            CashBoxSeeder::class,
            PurchaseInvoiceSeeder::class,
            SupplierDebtPaymentSeeder::class,
            CustomerSeeder::class,
            SalesInvoiceSeeder::class,
            CustomerDebtPaymentSeeder::class,
            SupplierReturnInvoiceSeeder::class,
            CustomerReturnInvoiceSeeder::class,
        ]);
    }
}
