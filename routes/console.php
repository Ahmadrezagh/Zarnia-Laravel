<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Update gold price from API every 10 minutes
Schedule::command('gold:update-price')->everyTenMinutes();

// Recalculate all product prices based on current gold price (choose frequency based on your needs)
Schedule::command('products:update-prices')->everyThirtyMinutes();
// Alternative frequencies:
// ->hourly() - once per hour
// ->everyTenMinutes() - same as gold price updates
// ->everySixHours() - for very large catalogs

Schedule::command('orders:mark-pending-as-failed')->everyTenMinutes();

Schedule::command('products:update-visits')->daily();