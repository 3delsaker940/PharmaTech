<?php

namespace App\Services;

use App\Models\CustomerReturnInvoice;
use App\Models\CustomerReturnItem;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerReturnInvoiceService
{
    public function __construct(
        private readonly StockService   $stockService,
        private readonly CashBoxService $cashBoxService,
    ) {}
    public function store(Pharmacy $pharmacy, User $user, array $data): CustomerReturnInvoice
    {
        return DB::transaction(function () use ($pharmacy, $user, $data) {
            $totals= $this->calculateTotals($data['items']);
            $refundTotal = $totals['refund_total'];

            $invoice = CustomerReturnInvoice::create([
                'pharmacy_id' => $pharmacy->id,
                'customer_id' => $data['customer_id'] ?? null,
                'original_sales_invoice_id' => $data['original_sales_invoice_id'] ?? null,
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
            foreach ($data['items'] as $itemData) {
                $this->processItem($invoice, $pharmacy, $user, $itemData);
            }

            if ($data['refund_method'] === 'cash' && $refundTotal > 0) {
                $cashBox = $this->cashBoxService->getCashBox($pharmacy->id);
                if ($cashBox) {
                    $this->cashBoxService->recordForCustomerReturn($cashBox, $invoice, $user);
                }
            }
            return $invoice->load(['items.product', 'customer', 'originalSalesInvoice', 'createdBy']);
        });
    }

    public function cancel(CustomerReturnInvoice $invoice, User $user): CustomerReturnInvoice
    {
        if ($invoice->status === 'cancelled') {
            throw new \InvalidArgumentException('This return invoice has already been cancelled.');
        }

        return DB::transaction(function () use ($invoice, $user) {
            $invoice->update(['status' => 'cancelled']);
            $this->reverseStock($invoice, $user);
            if ($invoice->refund_method === 'cash' && $invoice->refund_total > 0) {
                $cashBox = $this->cashBoxService->getCashBox($invoice->pharmacy_id);
                if ($cashBox) {
                    $this->cashBoxService->reverseCustomerReturn($cashBox, $invoice, $user);
                }
            }
            return $invoice->fresh(['items.product', 'customer', 'originalSalesInvoice', 'createdBy']);
        });
    }

    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return CustomerReturnInvoice::where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['status'] ?? null),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn ($q) => $q->where('customer_id', $filters['customer_id'])
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($q) => $q->whereDate('invoice_date', '>=', $filters['date_from'])
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($q) => $q->whereDate('invoice_date', '<=', $filters['date_to'])
            )
            ->with(['customer', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
    private function processItem(
        CustomerReturnInvoice $invoice,
        Pharmacy $pharmacy,
        User $user,
        array $itemData
    ): void {
        $productId = $itemData['product_id'];
        $quantity = (int) $itemData['quantity'];

        // Find the batch to restore to
        // Priority 1: original sale movement batch (if linked to a sales invoice)
        $batchId = null;

        if ($invoice->original_sales_invoice_id) {
            $originalMovement = StockMovement::where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->original_sales_invoice_id)
                ->where('product_id', $productId)
                ->where('movement_type', 'sale_out')
                ->orderByDesc('created_at')
                ->first();

            $batchId = $originalMovement?->batch_id;
        }

        // Priority 2: most recently received active batch as fallback
        if (! $batchId) {
            $batch = StockBatch::where('pharmacy_id', $pharmacy->id)
                ->where('product_id', $productId)
                ->where('status', 'active')
                ->orderByDesc('received_at')
                ->first();
            $batchId = $batch?->id;
        }

        if ($batchId) {
            $batch = StockBatch::find($batchId);
            $batch->update([
                'quantity_on_hand' => $batch->quantity_on_hand + $quantity,
                'status'=> 'active',
            ]);
        }

        $this->stockService->recordMovement(
            pharmacyId: $pharmacy->id,
            productId: $productId,
            batchId: $batchId,
            movementType: 'customer_return_in',
            quantityChange: $quantity,
            createdBy: $user->id,
            referenceType: 'customer_return_invoice',
            referenceId: $invoice->id,
            notes: "Customer return — invoice {$invoice->invoice_number}",
        );

        $unitPrice = (float) $itemData['unit_price'];
        $tax = (float) ($itemData['tax'] ?? 0);
        $discount = (float) ($itemData['discount'] ?? 0);
        $lineTotal = round(($unitPrice * $quantity) - $discount + $tax, 2);

        CustomerReturnItem::create([
            'customer_return_invoice_id' => $invoice->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax' => $tax,
            'discount' => $discount,
            'line_total' => $lineTotal,
        ]);
    }
    private function reverseStock(CustomerReturnInvoice $invoice, User $user): void
    {
        $movements = StockMovement::where('reference_type', 'customer_return_invoice')
            ->where('reference_id', $invoice->id)
            ->where('movement_type', 'customer_return_in')
            ->get();

        foreach ($movements as $movement) {
            $batch = StockBatch::find($movement->batch_id);

            if (! $batch) {
                continue;
            }

            $deductQty = abs($movement->quantity_change);
            $newQuantity = max(0, $batch->quantity_on_hand - $deductQty);

            $batch->update([
                'quantity_on_hand' => $newQuantity,
                'status' => $newQuantity === 0 ? 'depleted' : $batch->status,
            ]);

            $this->stockService->recordMovement(
                pharmacyId: $invoice->pharmacy_id,
                productId: $movement->product_id,
                batchId: $batch->id,
                movementType: 'adjustment_out',
                quantityChange: -$deductQty,
                createdBy: $user->id,
                referenceType: 'customer_return_invoice',
                referenceId: $invoice->id,
                notes: "Stock reversal — customer return invoice {$invoice->invoice_number} cancelled",
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
            'discount_total'=> round($discountTotal, 2),
            'refund_total' => round($subtotal - $discountTotal + $taxTotal, 2),
        ];
    }

    private function generateInvoiceNumber(int $pharmacyId): string
    {
        $year = now()->year;
        $prefix = 'CRI-' . $year . '-';

        $last = CustomerReturnInvoice::where('pharmacy_id', $pharmacyId)
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
