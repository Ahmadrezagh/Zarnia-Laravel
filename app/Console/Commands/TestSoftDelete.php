<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TestSoftDelete extends Command
{
    protected $signature = 'test:soft-delete';
    protected $description = 'Test soft delete functionality for orders';

    public function handle()
    {
        $this->info('Testing Soft Delete Setup...');
        $this->newLine();
        
        // Check if deleted_at column exists
        if (Schema::hasColumn('orders', 'deleted_at')) {
            $this->info('✓ deleted_at column exists in orders table');
        } else {
            $this->error('✗ deleted_at column DOES NOT exist in orders table');
            $this->warn('Run: php artisan migrate');
            return 1;
        }
        
        // Check if SoftDeletes trait is used
        $order = new Order();
        $traits = class_uses_recursive($order);
        
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', $traits)) {
            $this->info('✓ SoftDeletes trait is used in Order model');
        } else {
            $this->error('✗ SoftDeletes trait is NOT used in Order model');
            return 1;
        }
        
        // Check casts
        $casts = $order->getCasts();
        if (isset($casts['deleted_at'])) {
            $this->info('✓ deleted_at is cast as: ' . $casts['deleted_at']);
        } else {
            $this->warn('⚠ deleted_at is not explicitly cast (may still work)');
        }
        
        // Count orders
        $totalOrders = Order::withTrashed()->count();
        $activeOrders = Order::count();
        $trashedOrders = Order::onlyTrashed()->count();
        
        $this->newLine();
        $this->info("Orders Statistics:");
        $this->line("  Total Orders (including trashed): {$totalOrders}");
        $this->line("  Active Orders: {$activeOrders}");
        $this->line("  Trashed Orders: {$trashedOrders}");
        
        $this->newLine();
        $this->info('✓ Soft delete setup is correct!');
        $this->info('If delete is not working, try: php artisan cache:clear && php artisan config:clear');
        
        return 0;
    }
}

