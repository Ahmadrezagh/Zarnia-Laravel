<?php

namespace App\Console\Commands;

use App\Services\WordPressBlogSyncService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'blogs:sync-wordpress', description: 'Sync blog posts from WordPress API')]
class SyncWordPressBlogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blogs:sync-wordpress 
                            {--url= : WordPress API URL (default: https://delfigoldgallery.ir/wp-posts-api.php)}
                            {--per-page=100 : Number of posts per page}
                            {--no-update : Skip updating existing posts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync blog posts from WordPress API to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiUrl = $this->option('url') ?? 'https://delfigoldgallery.ir/wp-posts-api.php';
        $perPage = (int) ($this->option('per-page') ?? 100);
        $updateExisting = !$this->option('no-update');

        $this->info("Starting WordPress blog sync...");
        $this->info("API URL: {$apiUrl}");
        $this->info("Per page: {$perPage}");
        $this->info("Update existing: " . ($updateExisting ? 'Yes' : 'No'));
        $this->newLine();

        $service = new WordPressBlogSyncService($apiUrl, $perPage);
        
        $this->info("Fetching and syncing posts...");
        $stats = $service->syncAll($updateExisting);

        $this->newLine();
        $this->info("Sync completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn("Some errors occurred during sync. Check logs for details.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
