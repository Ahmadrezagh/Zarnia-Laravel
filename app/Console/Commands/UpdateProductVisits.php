<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-visits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product visits count from visits table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update product visits...');

        $products = Product::all();
        $totalProducts = $products->count();
        $updated = 0;

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        foreach ($products as $product) {
            // Encode the slug to match how it appears in URLs
            $encodedSlug = urlencode($product->slug);
            
            // Count visits where URL contains the product slug
            $visitsCount = Visit::where('url', 'like', "%{$encodedSlug}%")
                ->count();

            // Update the product's visits field
            $product->visits = $visitsCount;
            $product->saveQuietly(); // Use saveQuietly to avoid triggering events

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated visits for {$updated} products.");

        return Command::SUCCESS;
    }
}
