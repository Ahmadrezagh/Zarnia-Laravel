<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::call(function () {
    getEtikets();
})->everyFifteenMinutes();
Schedule::call(function () {
    getUpdatedEtikets();
})->everyTwoMinutes();