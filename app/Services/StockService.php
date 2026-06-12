<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function createBatchFromPurchaseItem(
        PurchaseInvoiceItem $item,
        PurchaseInvoice $invoice,
        array $itemData
    ): StockBatch {
        $batchNumber = $itemData['batch_number']
            ?? $this->generateBatchNumber($invoice->pharmacy_id);

        $sellingPrice = $itemData['selling_price']
            ?? Product::find($item->product_id)->selling_price;

        return StockBatch::create([
            'pharmacy_id'         => $invoice->pharmacy_id,
            'product_id'          => $item->product_id,
            'purchase_invoice_id' => $invoice->id,
            'batch_number'        => $batchNumber,
            'expiry_date'         => $itemData['expiry_date'] ?? null,
            'purchase_price'      => $item->wholesale_price,
            'selling_price'       => $sellingPrice,
            'quantity_on_hand'    => $item->quantity,
            'received_at'         => now(),
            'status'              => 'active',
        ]);
    }
    private function generateBatchNumber(int $pharmacyId): string
    {
        $year   = now()->year;
        $prefix = 'BCH-' . $year . '-';

        $lastBatch = StockBatch::where('pharmacy_id', $pharmacyId)
            ->where('batch_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $sequence = 1;

        if ($lastBatch) {
            $lastSequence = (int) substr($lastBatch->batch_number, strlen($prefix));
            $sequence     = $lastSequence + 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
