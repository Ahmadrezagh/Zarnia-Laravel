<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orders:mark-pending-as-failed')]
class MarkPendingOrdersAsFailed extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark pending orders as failed if more than 32 minutes have passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = Carbon::now()->subMinutes(32);
        $updated = 0;

        Order::query()
            ->where('status', Order::$STATUSES[0]) // pending
            ->where('created_at', '<=', $cutoff)
            ->chunkById(100, function ($orders) use (&$updated) {
                foreach ($orders as $order) {
                    $order->update(['status' => Order::$STATUSES[2]]);
                    $updated++;
                }
            });

        $this->info(sprintf('Marked %d pending orders as failed.', $updated));

        return Command::SUCCESS;
    }
}

