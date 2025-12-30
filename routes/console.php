<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('gold:update-price')->everyTenMinutes();

Schedule::command('orders:mark-pending-as-failed')->everyTenMinutes();

Schedule::command('products:update-visits')->daily();