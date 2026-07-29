<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierReturnInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SupplierReturnInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::first();
        $owner    = User::where('pharmacy_id', $pharmacy?->id)->first();

        if (! $pharmacy || ! $owner) {
            $this->command->warn('No pharmacy or owner found. Run DatabaseSeeder first.');
            return;
        }

        $supplier = Supplier::where('pharmacy_id', $pharmacy->id)
            ->where('name', 'MedPharma Distribution')
            ->first();

        if (! $supplier) {
            $this->command->warn('Supplier not found. Run PurchaseInvoiceSeeder first.');
            return;
        }

        // Get the first purchase invoice from MedPharma
        $originalInvoice = PurchaseInvoice::where('pharmacy_id', $pharmacy->id)
            ->where('supplier_id', $supplier->id)
            ->first();

        $panadol = Product::where('pharmacy_id', $pharmacy->id)
            ->where('brand_name', 'Panadol Extra')
            ->first();

        $amoxil = Product::where('pharmacy_id', $pharmacy->id)
            ->where('brand_name', 'Amoxil 500mg')
            ->first();

        if (! $panadol || ! $amoxil) {
            $this->command->warn('Required products not found. Run ProductSeeder first.');
            return;
        }

        /** @var SupplierReturnInvoiceService $service */
        $service = app(SupplierReturnInvoiceService::class);

        $today = Carbon::today();

        // Return 1 — linked to original purchase invoice, cash refund
        $service->store($pharmacy, $owner, [
            'supplier_id'                  => $supplier->id,
            'original_purchase_invoice_id' => $originalInvoice?->id,
            'invoice_date'                 => $today->copy()->subDays(2)->toDateString(),
            'refund_method'                => 'cash',
            'reason'                       => 'Expired products received',
            'notes'                        => 'Seeded — supplier return with original invoice',
            'items'                        => [
                [
                    'product_id' => $panadol->id,
                    'quantity'   => 5,
                    'unit_price' => 4500,
                    'tax'        => 0,
                    'discount'   => 0,
                ],
            ],
        ]);

        // Return 2 — no original invoice, credit refund
        $service->store($pharmacy, $owner, [
            'supplier_id'                  => $supplier->id,
            'original_purchase_invoice_id' => null,
            'invoice_date'                 => $today->toDateString(),
            'refund_method'                => 'credit',
            'reason'                       => 'Damaged packaging',
            'notes'                        => 'Seeded — supplier return, credit refund',
            'items'                        => [
                [
                    'product_id' => $amoxil->id,
                    'quantity'   => 3,
                    'unit_price' => 12000,
                    'tax'        => 0,
                    'discount'   => 0,
                ],
            ],
        ]);
    }
}
