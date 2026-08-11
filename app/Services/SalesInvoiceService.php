<?php

namespace App\Services;

use App\Models\CustomerDebt;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class SalesInvoiceService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CashBoxService $cashBoxService,
        private readonly NotificationService $notifier,
    ) {}
   public function store(Pharmacy $pharmacy, User $user, array $data): SalesInvoice
{
    $result = DB::transaction(function () use ($pharmacy, $user, $data) {
        $totals = $this->calculateTotals($data['items']);

       $grandTotal = $totals['grand_total'];

$amountPaid = min(
    (float) $data['amount_paid'],
    $grandTotal
);

$amountDue = max(
    0,
    round($grandTotal - $amountPaid, 2)
);

        if ($amountDue > 0 && empty($data['customer_id'])) {
            throw new \InvalidArgumentException(
                'A customer must be selected when the invoice is not fully paid.'
            );
        }

        foreach ($data['items'] as $itemData) {
            $this->assertSufficientStock($pharmacy, $itemData);
        }

        $paymentStatus = $this->resolvePaymentStatus($amountPaid, $grandTotal);

        $invoice = SalesInvoice::create([
            'pharmacy_id' => $pharmacy->id,
            'customer_id' => $data['customer_id'] ?? null,
            'created_by' => $user->id,
            'invoice_number' => $this->generateInvoiceNumber($pharmacy->id),
            'invoice_date' => $data['invoice_date'],
            'subtotal' => $totals['subtotal'],
            'tax_total' => $totals['tax_total'],
            'discount_total' => $totals['discount_total'],
            'grand_total' => $grandTotal,
            'amount_paid' => $amountPaid,
            'amount_due' => $amountDue,
            'payment_method' => $data['payment_method'],
            'payment_status' => $paymentStatus,
            'status' => 'completed',
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $itemData) {
            $this->processItem($invoice, $pharmacy, $user, $itemData);
        }

        if (in_array($data['payment_method'], ['cash', 'credit']) && $amountPaid > 0) {
            $cashBox = $this->cashBoxService->getCashBox($pharmacy->id);

            if ($cashBox) {
                $this->cashBoxService->recordForSale(
                    $cashBox,
                    $amountPaid,
                    $invoice,
                    $user
                );
            }
        }

        $customerDebt = null;

        if ($amountDue > 0) {
            $customerDebt = CustomerDebt::create([
                'pharmacy_id' => $pharmacy->id,
                'customer_id' => $data['customer_id'],
                'sales_invoice_id' => $invoice->id,
                'total_amount' => $grandTotal,
                'paid_amount' => $amountPaid,
                'remaining_amount' => $amountDue,
                'due_date' => $data['due_date'] ?? null,
                'status' => $this->resolveDebtStatus($amountPaid, $grandTotal),
            ]);
        }

        return [
            'invoice' => $invoice->load([
                'items.product',
                'customer',
                'customerDebt',
                'createdBy',
            ]),
            'customerDebt' => $customerDebt,
        ];
    });

   $invoice = $result['invoice'];
$customerDebt = $result['customerDebt'];

$this->notifier->sendToPharmacy(
    $pharmacy,
    'New Sales Invoice',
    "Sales invoice {$invoice->invoice_number} has been created. Total: {$invoice->grand_total}.",
    [
        'type' => 'sale_invoice_created',
        'pharmacy_id' => $pharmacy->id,
        'sales_invoice_id' => $invoice->id,
        'invoice_number' => $invoice->invoice_number,
        'customer_id' => $invoice->customer_id,
        'grand_total' => $invoice->grand_total,
        'amount_paid' => $invoice->amount_paid,
        'amount_due' => $invoice->amount_due,
        'payment_status' => $invoice->payment_status,
    ]
);

if ($customerDebt) {
    $this->notifier->sendToPharmacy(
        $pharmacy,
        'New Customer Debt',
        "Customer {$invoice->customer->full_name} has a new debt of {$customerDebt->remaining_amount}.",
        [
            'type' => 'customer_debt_created',
            'pharmacy_id' => $pharmacy->id,
            'customer_debt_id' => $customerDebt->id,
            'customer_id' => $customerDebt->customer_id,
            'sales_invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $customerDebt->total_amount,
            'remaining_amount' => $customerDebt->remaining_amount,
            'status' => $customerDebt->status,
        ]
    );
}

return $invoice;
}
    public function update(SalesInvoice $invoice, array $data): SalesInvoice
    {
        $invoice->update(['notes' => $data['notes'] ?? $invoice->notes]);
        return $invoice->fresh(['items.product', 'customer', 'customerDebt', 'createdBy']);
    }

public function cancel(SalesInvoice $invoice, User $user): SalesInvoice
{
    if ($invoice->status === 'cancelled') {
        throw new \InvalidArgumentException(
            'This invoice has already been cancelled.'
        );
    }

    $invoice = DB::transaction(function () use ($invoice, $user) {

        $invoice->update([
            'status' => 'cancelled',
        ]);

        $this->reverseStock($invoice, $user);

        if (
            in_array($invoice->payment_method, ['cash', 'credit'])
            && $invoice->amount_paid > 0
        ) {
            $cashBox = $this->cashBoxService->getCashBox(
                $invoice->pharmacy_id
            );

            if ($cashBox) {
                $this->cashBoxService->refundFromSaleCancellation(
                    $cashBox,
                    $invoice,
                    $user
                );
            }
        }

        if ($invoice->customerDebt) {
            $invoice->customerDebt->update([
                'status' => 'cancelled',
            ]);
        }

        return $invoice->fresh([
            'items.product',
            'customer',
            'customerDebt',
            'createdBy',
        ]);
    });

    // Notify pharmacy users after successful cancellation
    $this->notifier->sendToPharmacy(
        Pharmacy::findOrFail($invoice->pharmacy_id),
        'Sales Invoice Cancelled',
        "Sales invoice {$invoice->invoice_number} has been cancelled.",
        [
            'type' => 'sale_invoice_cancelled',
            'pharmacy_id' => $invoice->pharmacy_id,
            'sales_invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'grand_total' => $invoice->grand_total,
            'amount_paid' => $invoice->amount_paid,
            'amount_due' => $invoice->amount_due,
            'payment_status' => $invoice->payment_status,
            'cancelled_by' => $user->id,
        ]
    );

    return $invoice;
}



    public function list(Pharmacy $pharmacy, array $filters = []): LengthAwarePaginator
    {
        return SalesInvoice::where('pharmacy_id', $pharmacy->id)
            ->when(
                filled($filters['status'] ?? null),
                fn($q) => $q->where('status', $filters['status'])
            )
            ->when(
                filled($filters['payment_status'] ?? null),
                fn($q) => $q->where('payment_status', $filters['payment_status'])
            )
            ->when(
                filled($filters['payment_method'] ?? null),
                fn($q) => $q->where('payment_method', $filters['payment_method'])
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn($q) => $q->where('customer_id', $filters['customer_id'])
            )
            ->when(
                isset($filters['walk_in']) && filter_var($filters['walk_in'], FILTER_VALIDATE_BOOLEAN),
                fn($q) => $q->whereNull('customer_id')
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from'])
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to'])
            )
            ->with(['customer', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
    private function processItem(
        SalesInvoice $invoice,
        Pharmacy $pharmacy,
        User $user,
        array $itemData
    ): void {
        $productId = $itemData['product_id'];
        $quantity = (int) $itemData['quantity'];
        $remainingNeeded = $quantity;
        $batches = StockBatch::where('pharmacy_id', $pharmacy->id)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remainingNeeded <= 0) {
                break;
            }

            $deduct = min($remainingNeeded, $batch->quantity_on_hand);
            $newQuantity = $batch->quantity_on_hand - $deduct;

            $batch->update([
                'quantity_on_hand' => $newQuantity,
                'status'  => $newQuantity === 0 ? 'depleted' : $batch->status,
            ]);
            $this->stockService->recordMovement(
                pharmacyId: $pharmacy->id,
                productId: $productId,
                batchId: $batch->id,
                movementType: 'sale_out',
                quantityChange: -$deduct,
                createdBy: $user->id,
                referenceType: 'sales_invoice',
                referenceId: $invoice->id,
                notes: "Sale — invoice {$invoice->invoice_number}",
            );
            $remainingNeeded -= $deduct;
        }
        $sellingPrice = (float) $itemData['selling_price'];
        $tax = (float) ($itemData['tax'] ?? 0);
        $discount = (float) ($itemData['discount'] ?? 0);
        $lineTotal = round(($sellingPrice * $quantity) - $discount + $tax, 2);

        SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'selling_price' => $sellingPrice,
            'tax' => $tax,
            'discount' => $discount,
            'line_total' => $lineTotal,
        ]);
    }

    private function reverseStock(SalesInvoice $invoice, User $user): void
    {
        $movements = StockMovement::where('reference_type', 'sales_invoice')
            ->where('reference_id', $invoice->id)
            ->where('movement_type', 'sale_out')
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
                movementType: 'sale_return_in',
                quantityChange: $restoreQty,
                createdBy: $user->id,
                referenceType: 'sales_invoice',
                referenceId: $invoice->id,
                notes: "Stock restored — sales invoice {$invoice->invoice_number} cancelled",
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
        $discountTotal  = 0;

        foreach ($items as $item) {
            $gross = (float) $item['selling_price'] * (int) $item['quantity'];
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
            'grand_total' => round($subtotal - $discountTotal + $taxTotal, 2),
        ];
    }
    private function resolvePaymentStatus(float $amountPaid, float $grandTotal): string
    {
        if ($amountPaid <= 0) return 'unpaid';
        if ($amountPaid >= $grandTotal) return 'paid';
        return 'partial';
    }
    private function resolveDebtStatus(float $amountPaid, float $grandTotal): string
    {
        if ($amountPaid <= 0) return 'open';
        if ($amountPaid >= $grandTotal) return 'paid';
        return 'partial';
    }
    private function generateInvoiceNumber(int $pharmacyId): string
    {
        $year = now()->year;
        $prefix = 'SI-' . $year . '-';

        $last = SalesInvoice::where('pharmacy_id', $pharmacyId)
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
