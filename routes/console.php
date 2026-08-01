<?php

use App\Console\Commands\AnalyzeStockWithWeather;
use App\Console\Commands\CheckExpiringProducts;
use App\Console\Commands\CheckOverdueCustomerDebts;
use App\Console\Commands\CheckOverdueSupplierDebts;
use App\Console\Commands\NotifyExpiringSoonProducts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckExpiringProducts::class)->dailyAt('05:00')->withoutOverlapping();
Schedule::command(NotifyExpiringSoonProducts::class)->dailyAt('05:05')->withoutOverlapping();
Schedule::command(CheckOverdueSupplierDebts::class)->dailyAt('05:15')->withoutOverlapping();
Schedule::command(CheckOverdueCustomerDebts::class)->dailyAt('05:20')->withoutOverlapping();
Schedule::command(AnalyzeStockWithWeather::class)->weekly()->fridays()->at('06:00')->withoutOverlapping();
