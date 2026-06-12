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
    public function recordMovement(
        int $pharmacyId,
        int $productId,
        ?int $batchId,
        string $movementType,
        int $quantityChange,
        int $createdBy,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return StockMovement::create([
            'pharmacy_id'     => $pharmacyId,
            'product_id'      => $productId,
            'batch_id'        => $batchId,
            'movement_type'   => $movementType,
            'quantity_change' => $quantityChange,
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'created_by'      => $createdBy,
            'notes'           => $notes,
        ]);
    }
    public function manualAdjustment(Pharmacy $pharmacy, User $user, array $data): array
    {
        if ($data['adjustment_type'] === 'add') {
            return $this->addStock($pharmacy, $user, $data);
        }

        return $this->removeStock($pharmacy, $user, $data);
    }

    public function bulkAdjustment(Pharmacy $pharmacy, User $user, array $items): array
    {
        return DB::transaction(function () use ($pharmacy, $user, $items) {
            $results = [];

            foreach ($items as $item) {
                $results[] = $this->manualAdjustment($pharmacy, $user, $item);
            }

            return $results;
        });
    }

    public function reverseBatchesFromCancellation(PurchaseInvoice $invoice, User $user): void
    {
        $batches = StockBatch::where('purchase_invoice_id', $invoice->id)->get();

        foreach ($batches as $batch) {
            $quantityToReverse = $batch->quantity_on_hand;

            $batch->update([
                'quantity_on_hand' => 0,
                'status'           => 'inactive',
            ]);

            if ($quantityToReverse > 0) {
                $this->recordMovement(
                    pharmacyId:     $invoice->pharmacy_id,
                    productId:      $batch->product_id,
                    batchId:        $batch->id,
                    movementType:   'adjustment_out',
                    quantityChange: -$quantityToReverse,
                    createdBy:      $user->id,
                    referenceType:  'purchase_invoice',
                    referenceId:    $invoice->id,
                    notes:          "Stock reversed — invoice {$invoice->invoice_number} cancelled",
                );
            }
        }
    }

    private function addStock(Pharmacy $pharmacy, User $user, array $data): array
    {
        $batchNumber = $data['batch_number'] ?? $this->generateBatchNumber($pharmacy->id);

        $batch = StockBatch::create([
            'pharmacy_id'         => $pharmacy->id,
            'product_id'          => $data['product_id'],
            'purchase_invoice_id' => null,
            'batch_number'        => $batchNumber,
            'expiry_date'         => $data['expiry_date'] ?? null,
            'purchase_price'      => $data['purchase_price'],
            'selling_price'       => $data['selling_price'],
            'quantity_on_hand'    => $data['quantity'],
            'received_at'         => now(),
            'status'              => 'active',
        ]);

        $movement = $this->recordMovement(
            pharmacyId:     $pharmacy->id,
            productId:      $data['product_id'],
            batchId:        $batch->id,
            movementType:   'adjustment_in',
            quantityChange: $data['quantity'],
            createdBy:      $user->id,
            notes:          $data['notes'] ?? 'Manual stock addition',
        );

        return ['batch' => $batch, 'movement' => $movement];
    }

    private function removeStock(Pharmacy $pharmacy, User $user, array $data): array
    {
        $batch = StockBatch::where('pharmacy_id', $pharmacy->id)
            ->where('id', $data['batch_id'])
            ->firstOrFail();

        if ($batch->quantity_on_hand < $data['quantity']) {
            throw new \InvalidArgumentException(
                "Cannot remove {$data['quantity']} units. " .
                "Only {$batch->quantity_on_hand} available in batch {$batch->batch_number}."
            );
        }

        $newQuantity = $batch->quantity_on_hand - $data['quantity'];

        $batch->update([
            'quantity_on_hand' => $newQuantity,
            'status'           => $newQuantity === 0 ? 'depleted' : $batch->status,
        ]);

        $movement = $this->recordMovement(
            pharmacyId:     $pharmacy->id,
            productId:      $batch->product_id,
            batchId:        $batch->id,
            movementType:   'adjustment_out',
            quantityChange: -$data['quantity'],
            createdBy:      $user->id,
            notes:          $data['notes'] ?? 'Manual stock removal',
        );

        return ['batch' => $batch->fresh(), 'movement' => $movement];
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
