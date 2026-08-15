<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Products
            'view-products',
            'create-products',
            'update-products',
            'delete-products',
            'restore-products',

            // Suppliers
            'view-suppliers',
            'create-suppliers',
            'update-suppliers',
            'delete-suppliers',
            'restore-suppliers',

            // Customers
            'view-customers',
            'create-customers',
            'update-customers',
            'delete-customers',
            'restore-customers',

            // Customer debts
            'view-customer-debts',
            'pay-customer-debts',

            // Supplier debts
            'view-supplier-debts',
            'pay-supplier-debts',

            // Purchase invoices
            'view-purchase-invoices',
            'create-purchase-invoices',
            'update-purchase-invoices',
            'cancel-purchase-invoices',

            // Sales invoices
            'view-sales-invoices',
            'create-sales-invoices',
            'update-sales-invoices',
            'cancel-sales-invoices',

            // Customer returns
            'view-customer-returns',
            'create-customer-returns',
            'cancel-customer-returns',

            // Supplier returns
            'view-supplier-returns',
            'create-supplier-returns',
            'cancel-supplier-returns',

            // Stock (batches, movements, adjustments)
            'view-stock',
            'manage-stock',

            // Cash box
            'view-cash-box',
            'manage-cash-box',

            // Catalog (global lookups)
            'view-categories',
            'view-companies',
            'view-units',

            // Inventory helper endpoints
            'view-inventory',
            'view-inventory-predictions',
            'check-drug-interactions',

            // Dashboard & reports
            'view-dashboard',
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ---- Roles ----
        $systemAdmin = Role::firstOrCreate(['name' => 'system_admin', 'guard_name' => 'web']);
        $pharmacyOwner = Role::firstOrCreate(['name' => 'pharmacy_owner', 'guard_name' => 'web']);
        $pharmacist = Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web']);

        // system_admin: full access to everything.
        $systemAdmin->syncPermissions($permissions);

        // pharmacy_owner: full operational access within their own pharmacy,
        // including destructive/financial actions and restores.
        $pharmacyOwner->syncPermissions([
            'view-products', 'create-products', 'update-products', 'delete-products', 'restore-products',
            'view-suppliers', 'create-suppliers', 'update-suppliers', 'delete-suppliers', 'restore-suppliers',
            'view-customers', 'create-customers', 'update-customers', 'delete-customers', 'restore-customers',
            'view-customer-debts', 'pay-customer-debts',
            'view-supplier-debts', 'pay-supplier-debts',
            'view-purchase-invoices', 'create-purchase-invoices', 'update-purchase-invoices', 'cancel-purchase-invoices',
            'view-sales-invoices', 'create-sales-invoices', 'update-sales-invoices', 'cancel-sales-invoices',
            'view-customer-returns', 'create-customer-returns', 'cancel-customer-returns',
            'view-supplier-returns', 'create-supplier-returns', 'cancel-supplier-returns',
            'view-stock', 'manage-stock',
            'view-cash-box', 'manage-cash-box',
            'view-categories', 'view-companies', 'view-units',
            'view-inventory', 'view-inventory-predictions', 'check-drug-interactions',
            'view-dashboard', 'view-reports',
        ]);

        // pharmacist: day-to-day operational access, without deletes/restores
        // or cash box management.
        $pharmacist->syncPermissions([
            'view-products', 'create-products', 'update-products',
            'view-suppliers',
            'view-customers', 'create-customers', 'update-customers',
            'view-customer-debts', 'pay-customer-debts',
            'view-supplier-debts',
            'view-purchase-invoices', 'create-purchase-invoices',
            'view-sales-invoices', 'create-sales-invoices', 'update-sales-invoices', 'cancel-sales-invoices',
            'view-customer-returns', 'create-customer-returns',
            'view-supplier-returns',
            'view-stock', 'manage-stock',
            'view-cash-box',
            'view-categories', 'view-companies', 'view-units',
            'view-inventory', 'view-inventory-predictions', 'check-drug-interactions',
            'view-dashboard', 'view-reports',
        ]);
    }
}
