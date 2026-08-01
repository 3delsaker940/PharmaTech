<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\StockBatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pharmacy:notify-expiring-soon-products')]
#[Description('Notify pharmacy owners about batches expiring within 30 days')]
class NotifyExpiringSoonProducts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = Carbon::now()->addMonth();
        StockBatch::where('expiry_date', '<=', $month)->get();
    }
}
