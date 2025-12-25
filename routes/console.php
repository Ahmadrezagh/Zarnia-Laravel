<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    getEtikets();
})->everyFiveMinutes();
Schedule::call(function () {
    getUpdatedEtikets();
})->everyFiveSeconds();

Schedule::call(function () {
    $tabanGohar = new \App\Services\Api\TabanGohar();
    $tabanGohar->updateGoldPrice();
})->everyTenMinutes();

Schedule::command('orders:mark-pending-as-failed')->everyTenMinutes();

Schedule::command('products:update-visits')->daily();