<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    getEtikets();
})->everyThirtyMinutes();
Schedule::call(function () {
    getUpdatedEtikets();
})->everyFiveSeconds();

Schedule::command('orders:mark-pending-as-failed')->everyTenMinutes();