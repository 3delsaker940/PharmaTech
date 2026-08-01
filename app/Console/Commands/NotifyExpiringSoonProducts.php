<?php

namespace App\Console\Commands;

use App\Models\StockBatch;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pharmacy:notify-expiring-soon-products')]
#[Description('Notify pharmacy users about batches expiring within 30 days')]
class NotifyExpiringSoonProducts extends Command
{
    /**
     * There is no "owner" concept in the project yet (no roles/permissions
     * table, no owner_id column) — every user belonging to a pharmacy is
     * currently equivalent. Until that exists, notifications go to every
     * user of the affected pharmacy.
     */
    public function handle(NotificationService $notifier)
    {
        $threshold = Carbon::now()->addMonth();

        $batches = StockBatch::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $threshold)
            ->whereDate('expiry_date', '>=', now())
            ->with(['pharmacy.users', 'product'])
            ->get();

        $batches->groupBy('pharmacy_id')->each(function ($pharmacyBatches) use ($notifier) {
            $pharmacy = $pharmacyBatches->first()->pharmacy;

            if (!$pharmacy || $pharmacy->users->isEmpty()) {
                return;
            }

            $count = $pharmacyBatches->count();

            $productLines = $pharmacyBatches
                ->map(function (StockBatch $batch) {
                    $productName = $batch->product->brand_name ?? "Product #{$batch->product_id}";
                    return "{$productName} (Batch {$batch->batch_number}, expires {$batch->expiry_date})";
                })
                ->implode(', ');

            $title = $count === 1
                ? 'Product Expiring Soon'
                : "{$count} Products Expiring Soon";

            $body = $count === 1
                ? "{$productLines} will expire within 30 days."
                : "The following batches will expire within 30 days: {$productLines}.";

            $data = [
                'type'        => 'batch_expiring_soon',
                'pharmacy_id' => $pharmacy->id,
                'batch_ids'   => $pharmacyBatches->pluck('id')->all(),
                'product_ids' => $pharmacyBatches->pluck('product_id')->unique()->values()->all(),
            ];

            foreach ($pharmacy->users as $recipient) {
                $notifier->sendAndSave($recipient, $title, $body, $data);
            }
        });
    }
}
