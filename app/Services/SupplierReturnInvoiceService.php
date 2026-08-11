<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\SupplierReturnInvoice;
use App\Models\SupplierReturnItem;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierReturnInvoiceService
{
    public function __construct(
        private readonly StockService   $stockService,
        private readonly CashBoxService $cashBoxService,
        private readonly NotificationService $notifier,
    ) {}

    public function store(Pharmacy $pharmacy, User $user, array $data): SupplierReturnInvoice
    {
        $result = DB::transaction(function () use ($pharmacy, $user, $data) {

            if (! empty($data['original_purchase_invoice_id'])) {
                $this->validateReturnQuantities($pharmacy, (int) $data['original_purchase_invoice_id'], $data['items']);
            }

            foreach ($data['items'] as $itemData) {
                $this->assertSufficientStock($pharmacy, $itemData);
            }
            $totals      = $this->calculateTotals($data['items']);
            $refundTotal = $totals['refund_total'];

            $invoice = SupplierReturnInvoice::create([
                'pharmacy_id' => $pharmacy->id,
                'supplier_id' => $data['supplier_id'],
                'original_purchase_invoice_id' => $data['original_purchase_invoice_id'] ?? null,
                'created_by' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber($pharmacy->id),
                'invoice_date' => $data['invoice_date'],
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'discount_total' => $totals['discount_total'],
                'refund_total' => $refundTotal,
                'refund_method' => $data['refund_method'],
                'status' => 'completed',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $affectedProductIds = [];

            foreach ($data['items'] as $itemData) {
                $this->processItem($invoice, $pharmacy, $user, $itemData);
                $affectedProductIds[] = $itemData['product_id'];
            }

            if ($data['refund_method'] === 'cash' && $refundTotal > 0) {
                $cashBox = $this->cashBoxService->getCashBox($pharmacy->id);
                if ($cashBox) {
                    $this->cashBoxService->recordForSupplierReturn($cashBox, $invoice, $user);
                }
            }

            return [
                'invoice' => $invoice->load(['items.product', 'supplier', 'originalPurchaseInvoice', 'createdBy']),
                'affectedProductIds' => array_unique($affectedProductIds),
            ];
        });

        $invoice = $result['invoice'];
        $affectedProductIds = $result['affectedProductIds'];

        foreach ($affectedProductIds as $productId) {
            $this->stockService->checkLowStock(Product::findOrFail($productId));
        }

        $this->notifier->sendToPharmacy(
            $pharmacy,
            'New Supplier Return',
            "Supplier return invoice {$invoice->invoice_number} has been created for supplier {$invoice->supplier->name}.",
            [
                'type' => 'supplier_return_created',
                'pharmacy_id' => $pharmacy->id,
                'supplier_return_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'supplier_id' => $invoice->supplier_id,
                'refund_total' => $invoice->refund_total,
            ]
        );

        return $invoice;
    }

    public function cancel(SupplierReturnInvoice $invoice, User $user): SupplierReturnInvoice
    {
        if ($invoice->status === 'cancelled') {
            throw new \InvalidArgumentException('This return invoice has already been cancelled.');
        }

        $invoice = DB::transaction(function () use ($invoice, $user) {
            $invoice->update(['status' => 'cancelled']);

            $this->reverseStock($invoice, $user);

            if ($invoice->refund_method === 'cash' && $invoice->refund_total > 0) {
                $cashBox = $this->cashBoxService->getCashBox($invoice->pharmacy_id);
                if ($cashBox) {
                    $this->cashBoxService->reverseSupplierReturn($cashBox, $invoice, $invoice->refund_total, $user);
                }
            }
            return $invoice->fresh(['items.product', 'supplier', 'originalPurchaseInvoice', 'createdBy']);
        });

        $this->notifier->sendToPharmacy(
            Pharmacy::findOrFail($invoice->pharmacy_id),
            'Supplier Return Cancelled',
            "Supplier return invoice {$invoice->invoice_number} has been cancelled.",
            [
                'type' => 'supplier_return_cancelled',
                'pharmacy_id' => $invoice->pharmacy_id,
                'supplier_return_invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'supplier_id' => $invoice->supplier_id,
                'refund_total' => $invoice->refund_total,
                'cancelled_by' => $user->id,
            ]
        );

        return $invoice;
    }

    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return SupplierReturnInvoice::where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['status'] ?? null),
                fn($q) => $q->where('status', $filters['status'])
            )
            ->when(
                filled($filters['supplier_id'] ?? null),
                fn($q) => $q->where('supplier_id', $filters['supplier_id'])
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from'])
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to'])
            )
            ->with(['supplier', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    private function validateReturnQuantities(Pharmacy $pharmacy, int $originalPurchaseInvoiceId, array $items): void
    {
        $requestedByProduct = [];
        foreach ($items as $itemData) {
            $productId = (int) $itemData['product_id'];
            $requestedByProduct[$productId] = ($requestedByProduct[$productId] ?? 0) + (int) $itemData['quantity'];
        }

        $productIds = array_keys($requestedByProduct);

        $purchasedByProduct = PurchaseInvoiceItem::where('purchase_invoice_id', $originalPurchaseInvoiceId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(quantity) as total_purchased')
            ->groupBy('product_id')
            ->pluck('total_purchased', 'product_id');

        $alreadyReturnedByProduct = SupplierReturnItem::query()
            ->join('supplier_return_invoices', 'supplier_return_invoices.id', '=', 'supplier_return_items.supplier_return_invoice_id')
            ->where('supplier_return_invoices.original_purchase_invoice_id', $originalPurchaseInvoiceId)
            ->where('supplier_return_invoices.status', '!=', 'cancelled')
            ->whereIn('supplier_return_items.product_id', $productIds)
            ->lockForUpdate()
            ->selectRaw('supplier_return_items.product_id, SUM(supplier_return_items.quantity) as total_returned')
            ->groupBy('supplier_return_items.product_id')
            ->pluck('total_returned', 'product_id');

        foreach ($requestedByProduct as $productId => $requestedQty) {
            $purchased = (int) ($purchasedByProduct[$productId] ?? 0);
            $alreadyReturned = (int) ($alreadyReturnedByProduct[$productId] ?? 0);
            $remaining = $purchased - $alreadyReturned;

            if ($purchased === 0) {
                $productName = Product::find($productId)?->brand_name ?? "#{$productId}";
                throw ValidationException::withMessages([
                    'items' => "Product \"{$productName}\" was not purchased on the selected purchase invoice.",
                ]);
            }

            if ($requestedQty > $remaining) {
                $productName = Product::find($productId)?->brand_name ?? "#{$productId}";
                throw ValidationException::withMessages([
                    'items' => "Cannot return {$requestedQty} unit(s) of \"{$productName}\": only {$remaining} unit(s) remain returnable for this purchase invoice (purchased: {$purchased}, already returned: {$alreadyReturned}).",
                ]);
            }
        }
    }

    private function processItem(
        SupplierReturnInvoice $invoice,
        Pharmacy $pharmacy,
        User $user,
        array $itemData
    ): void {
        $productId = $itemData['product_id'];
        $quantity = (int) $itemData['quantity'];
        $remainingNeeded = $quantity;

        // Priority 1: deduct from original purchase invoice batches first
        if ($invoice->original_purchase_invoice_id) {
            $batches = StockBatch::where('pharmacy_id', $pharmacy->id)
                ->where('product_id', $productId)
                ->where('purchase_invoice_id', $invoice->original_purchase_invoice_id)
                ->where('status', 'active')
                ->where('quantity_on_hand', '>', 0)
                ->orderBy('received_at')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0) break;

                $deduct = min($remainingNeeded, $batch->quantity_on_hand);
                $newQuantity = $batch->quantity_on_hand - $deduct;

                $batch->update([
                    'quantity_on_hand' => $newQuantity,
                    'status' => $newQuantity === 0 ? 'depleted' : $batch->status,
                ]);

                $this->stockService->recordMovement(
                    pharmacyId: $pharmacy->id,
                    productId: $productId,
                    batchId: $batch->id,
                    movementType: 'supplier_return_out',
                    quantityChange: -$deduct,
                    createdBy: $user->id,
                    referenceType: 'supplier_return_invoice',
                    referenceId: $invoice->id,
                    notes: "Supplier return — invoice {$invoice->invoice_number}",
                );
                $remainingNeeded -= $deduct;
            }
        }
        // Priority 2: FIFO fallback for remaining quantity
        if ($remainingNeeded > 0) {
            $batches = StockBatch::where('pharmacy_id', $pharmacy->id)
                ->where('product_id', $productId)
                ->where('status', 'active')
                ->where('quantity_on_hand', '>', 0)
                ->orderBy('received_at')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0) break;

                $deduct = min($remainingNeeded, $batch->quantity_on_hand);
                $newQuantity = $batch->quantity_on_hand - $deduct;

                $batch->update([
                    'quantity_on_hand' => $newQuantity,
                    'status' => $newQuantity === 0 ? 'depleted' : $batch->status,
                ]);

                $this->stockService->recordMovement(
                    pharmacyId: $pharmacy->id,
                    productId: $productId,
                    batchId: $batch->id,
                    movementType: 'supplier_return_out',
                    quantityChange: -$deduct,
                    createdBy: $user->id,
                    referenceType: 'supplier_return_invoice',
                    referenceId: $invoice->id,
                    notes: "Supplier return — invoice {$invoice->invoice_number}",
                );
                $remainingNeeded -= $deduct;
            }
        }

        $unitPrice = (float) $itemData['unit_price'];
        $tax = (float) ($itemData['tax'] ?? 0);
        $discount = (float) ($itemData['discount'] ?? 0);
        $lineTotal = round(($unitPrice * $quantity) - $discount + $tax, 2);

        SupplierReturnItem::create([
            'supplier_return_invoice_id' => $invoice->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax' => $tax,
            'discount' => $discount,
            'line_total' => $lineTotal,
        ]);
    }

    private function reverseStock(SupplierReturnInvoice $invoice, User $user): void
    {
        $movements = StockMovement::where('reference_type', 'supplier_return_invoice')
            ->where('reference_id', $invoice->id)
            ->where('movement_type', 'supplier_return_out')
            ->get();

        foreach ($movements as $movement) {
            $batch = StockBatch::find($movement->batch_id);

            if (! $batch) {
                continue;
            }

            $restoreQty = abs($movement->quantity_change);

            $batch->update([
                'quantity_on_hand' => $batch->quantity_on_hand + $restoreQty,
                'status' => 'active',
            ]);

            $this->stockService->recordMovement(
                pharmacyId: $invoice->pharmacy_id,
                productId: $movement->product_id,
                batchId: $batch->id,
                movementType: 'adjustment_in',
                quantityChange: $restoreQty,
                createdBy: $user->id,
                referenceType: 'supplier_return_invoice',
                referenceId: $invoice->id,
                notes: "Stock reversal — supplier return invoice {$invoice->invoice_number} cancelled",
            );
        }
    }

    private function assertSufficientStock(Pharmacy $pharmacy, array $itemData): void
    {
        $available = StockBatch::where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $itemData['product_id'])
            ->where('status', 'active')
            ->where('quantity_on_hand', '>', 0)
            ->sum('quantity_on_hand');

        if ($available < $itemData['quantity']) {
            $product = Product::find($itemData['product_id']);
            throw new \InvalidArgumentException(
                "Insufficient stock for '{$product->brand_name}'. " .
                    "Requested: {$itemData['quantity']}, Available: {$available}."
            );
        }
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        foreach ($items as $item) {
            $gross = (float) $item['unit_price'] * (int) $item['quantity'];
            $tax = (float) ($item['tax'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);

            $subtotal += $gross;
            $taxTotal += $tax;
            $discountTotal += $discount;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'refund_total' => round($subtotal - $discountTotal + $taxTotal, 2),
        ];
    }

    private function generateInvoiceNumber(int $pharmacyId): string
    {
        $year   = now()->year;
        $prefix = 'SRI-' . $year . '-';

        $last = SupplierReturnInvoice::where('pharmacy_id', $pharmacyId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $sequence = $last
            ? ((int) substr($last->invoice_number, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
