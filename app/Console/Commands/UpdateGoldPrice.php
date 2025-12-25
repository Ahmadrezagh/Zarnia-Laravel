<?php

namespace App\Console\Commands;

use App\Services\Api\TabanGohar;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'gold:update-price')]
class UpdateGoldPrice extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update gold price from Taban Gohar API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching gold price from Taban Gohar API...');

        $tabanGohar = new TabanGohar();
        $success = $tabanGohar->updateGoldPrice();

        if ($success) {
            $goldPrice = \App\Models\Setting::getValue('gold_price');
            $this->info("Gold price updated successfully: {$goldPrice} (YekGram18)");
            return Command::SUCCESS;
        }

        $this->error('Failed to update gold price. Check logs for details.');
        return Command::FAILURE;
    }
}

