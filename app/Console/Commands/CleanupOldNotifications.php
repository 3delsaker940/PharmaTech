<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppNotification;

class CleanupOldNotifications extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'Delete read notifications older than 1 month and unread older than 6 months';

    public function handle()
    {
        AppNotification::whereNotNull('read_at')
            ->where('created_at', '<=', now()->subMonth())
            ->delete();

        AppNotification::whereNull('read_at')
            ->where('created_at', '<=', now()->subMonths(6))
            ->delete();

        $this->info('Old notifications cleaned up successfully.');
    }
}
