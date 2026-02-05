<?php

namespace App\Console\Commands;

use App\Services\Api\TabanGohar;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'products:update-prices')]
class UpdateAllProductsPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-prices';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and update all product prices based on current gold price';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to recalculate all product prices...');
        $this->newLine();

        $goldPrice = \App\Models\Setting::getValue('gold_price');
        $this->info("Current gold price (YekGram18): {$goldPrice}");
        $this->newLine();

        if (!$goldPrice || $goldPrice <= 0) {
            $this->error('Gold price is not set or invalid. Please update gold price first using: php artisan gold:update-price');
            return Command::FAILURE;
        }

        $this->info('Updating product prices...');
        $this->info('This may take a while for large catalogs...');

        try {
            $tabanGohar = new TabanGohar();
            $tabanGohar->updateAllProductsPrices();

            $this->newLine();
            $this->info('✓ All product prices have been updated successfully!');
            $this->comment('Check the logs for detailed statistics.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to update product prices: ' . $e->getMessage());
            $this->error('Check logs for more details.');
            
            return Command::FAILURE;
        }
    }
}
