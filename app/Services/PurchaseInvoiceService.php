<?php

namespace App\Services;

use App\Models\Pharmacy;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CashBoxService $cashBoxService,
        private readonly SupplierDebtService $supplierDebtService,
    ) {}

    public function list(Pharmacy $pharmacy, array $filters): LengthAwarePaginator
    {
        return PurchaseInvoice::where('pharmacy_id', $pharmacy->id)
            ->when(
                $filters['supplier_id'] ?? null,
                fn ($q, $v) => $q->where('supplier_id', $v)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($q, $v) => $q->where('status', $v)
            )
            ->when(
                $filters['payment_status'] ?? null,
                fn ($q, $v) => $q->where('payment_status', $v)
            )
            ->when(
                $filters['from_date'] ?? null,
                fn ($q, $v) => $q->whereDate('invoice_date', '>=', $v)
            )
            ->when(
                $filters['to_date'] ?? null,
                fn ($q, $v) => $q->whereDate('invoice_date', '<=', $v)
            )
            ->with(['supplier', 'createdBy', 'supplierDebt'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function store(Pharmacy $pharmacy, User $user, array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($pharmacy, $user, $data) {
            $items         = $data['items'];
            $subtotal      = 0;
            $taxTotal      = 0;
            $discountTotal = 0;

            foreach ($items as $item) {
                $subtotal      += $item['quantity'] * $item['wholesale_price'];
                $taxTotal      += $item['tax'] ?? 0;
                $discountTotal += $item['discount'] ?? 0;
            }

            $grandTotal = round($subtotal + $taxTotal - $discountTotal, 2);
            $amountPaid = round(min((float) $data['amount_paid'], $grandTotal), 2);
            $amountDue  = round($grandTotal - $amountPaid, 2);

            $paymentStatus = match (true) {
                $amountDue <= 0  => 'paid',
                $amountPaid > 0  => 'partial',
                default          => 'unpaid',
            };

            $invoice = PurchaseInvoice::create([
                'pharmacy_id'    => $pharmacy->id,
                'supplier_id'    => $data['supplier_id'],
                'created_by'     => $user->id,
                'invoice_number' => $this->generateInvoiceNumber($pharmacy),
                'invoice_date'   => $data['invoice_date'],
                'subtotal'       => round($subtotal, 2),
                'tax_total'      => round($taxTotal, 2),
                'discount_total' => round($discountTotal, 2),
                'grand_total'    => $grandTotal,
                'amount_paid'    => $amountPaid,
                'amount_due'     => $amountDue,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentStatus,
                'status'         => 'completed',
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($items as $itemData) {
                $lineTotal = round(
                    ($itemData['quantity'] * $itemData['wholesale_price'])
                    + ($itemData['tax'] ?? 0)
                    - ($itemData['discount'] ?? 0),
                    2
                );

                $invoiceItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $itemData['product_id'],
                    'quantity'            => $itemData['quantity'],
                    'wholesale_price'     => $itemData['wholesale_price'],
                    'tax'                 => $itemData['tax'] ?? 0,
                    'discount'            => $itemData['discount'] ?? 0,
                    'line_total'          => $lineTotal,
                ]);

                $batch = $this->stockService->createBatchFromPurchaseItem(
                    $invoiceItem,
                    $invoice,
                    $itemData
                );

                $this->stockService->recordMovement(
                    pharmacyId:     $pharmacy->id,
                    productId:      $invoiceItem->product_id,
                    batchId:        $batch->id,
                    movementType:   'purchase_in',
                    quantityChange: $batch->quantity_on_hand,
                    createdBy:      $user->id,
                    referenceType:  'purchase_invoice',
                    referenceId:    $invoice->id,
                );
            }

            if ($data['payment_method'] === 'cash' && $amountPaid > 0) {
                $cashBox = $this->cashBoxService->getActiveBox($pharmacy->id);

                if ($cashBox) {
                    $this->cashBoxService->deductForPurchase(
                        $cashBox,
                        $amountPaid,
                        $invoice,
                        $user
                    );
                }
            }

            if ($amountDue > 0) {
                $this->supplierDebtService->createFromInvoice($invoice);
            }

            return $invoice->load([
                'supplier',
                'createdBy',
                'items.product',
                'supplierDebt',
            ]);
        });
    }

    public function update(PurchaseInvoice $invoice, array $data): PurchaseInvoice
    {
        $invoice->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return $invoice->fresh(['supplier', 'createdBy', 'items.product', 'supplierDebt']);
    }
    public function cancel(PurchaseInvoice $invoice, User $user): PurchaseInvoice
    {
        if ($invoice->status === 'cancelled') {
            throw new \InvalidArgumentException('This invoice is already cancelled.');
        }

        return DB::transaction(function () use ($invoice, $user) {
            $invoice->update(['status' => 'cancelled']);

            $this->stockService->reverseBatchesFromCancellation($invoice, $user);

            if ($invoice->payment_method === 'cash' && $invoice->amount_paid > 0) {
                $cashBox = $this->cashBoxService->getActiveBox($invoice->pharmacy_id);

                if ($cashBox) {
                    $this->cashBoxService->refundFromCancellation($cashBox, $invoice, $user);
                }
            }

            $this->supplierDebtService->cancelFromInvoice($invoice);

            return $invoice->fresh([
                'supplier',
                'createdBy',
                'items.product',
                'supplierDebt',
            ]);
        });
    }

    public function findById(Pharmacy $pharmacy, int $id): PurchaseInvoice
    {
        return PurchaseInvoice::where('pharmacy_id', $pharmacy->id)
            ->with([
                'supplier',
                'createdBy',
                'items.product',
                'supplierDebt.payments.createdBy',
            ])
            ->findOrFail($id);
    }

    private function generateInvoiceNumber(Pharmacy $pharmacy): string
    {
        $year   = now()->year;
        $prefix = 'PUR-' . $year . '-';

        $lastInvoice = PurchaseInvoice::where('pharmacy_id', $pharmacy->id)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $sequence = 1;

        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $sequence     = $lastSequence + 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
