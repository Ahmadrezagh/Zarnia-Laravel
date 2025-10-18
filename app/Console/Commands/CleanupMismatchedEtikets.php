<?php

namespace App\Console\Commands;

use App\Observers\EtiketObserver;
use Illuminate\Console\Command;

class CleanupMismatchedEtikets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'etikets:cleanup-mismatched';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disconnect etikets from products with mismatched names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of mismatched etikets...');
        
        $observer = app(EtiketObserver::class);
        $disconnected = $observer->cleanupMismatchedEtikets();
        
        if (count($disconnected) > 0) {
            $this->warn('Disconnected ' . count($disconnected) . ' etikets from mismatched products:');
            
            $this->table(
                ['Etiket ID', 'Etiket Name', 'Product ID', 'Product Name'],
                array_map(function($item) {
                    return [
                        $item['etiket_id'],
                        $item['etiket_name'],
                        $item['product_id'],
                        $item['product_name'],
                    ];
                }, $disconnected)
            );
        } else {
            $this->info('No mismatched etikets found. All connections are valid!');
        }
        
        $this->info('Cleanup completed successfully.');
        
        return Command::SUCCESS;
    }
}
