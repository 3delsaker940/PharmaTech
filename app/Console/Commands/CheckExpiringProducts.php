<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use App\Services\StockService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('pharmacy:check-expiring-stock')]
#[Description('Check for product batches past their expiry date, expire them, and notify pharmacy users')]
class CheckExpiringProducts extends Command
{
    public function handle(StockService $stockService, NotificationService $notifier): void
    {
        try {
            $expiredBatches = $stockService->expireOverdueBatches(createdBy: null);

            collect($expiredBatches)
                ->groupBy('pharmacy_id')
                ->each(function ($batches, $pharmacyId) use ($notifier) {
                    $pharmacy = $batches->first()->pharmacy;

                    if (!$pharmacy) {
                        return;
                    }

                    $recipients = $pharmacy->users;

                    if ($recipients->isEmpty()) {
                        return;
                    }

                    $count = $batches->count();

                    $productLines = $batches
                        ->map(function ($batch) {
                            $productName = $batch->product->brand_name ?? "Product #{$batch->product_id}";
                            return "{$productName} (Batch {$batch->batch_number})";
                        })
                        ->implode(', ');

                    $title = $count === 1
                        ? 'Batch Expired'
                        : "{$count} Batches Expired";

                    $body = $count === 1
                        ? "{$productLines} has expired and was written off automatically."
                        : "The following batches expired and were written off automatically: {$productLines}.";

                    $data = [
                        'type'        => 'batch_expired',
                        'pharmacy_id' => (int) $pharmacyId,
                        'batch_ids'   => $batches->pluck('id')->all(),
                        'product_ids' => $batches->pluck('product_id')->unique()->values()->all(),
                    ];

                    foreach ($recipients as $recipient) {
                        $notifier->sendAndSave($recipient, $title, $body, $data);
                    }
                });

            $this->info(count($expiredBatches) . ' batch(es) expired and notified.');
        } catch (\Exception $e) {
            Log::error('CheckExpiringProducts failed: ' . $e->getMessage());
            $this->error('Failed to process expiring stock. Check logs for details.');
        }
    }
}
