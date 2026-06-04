<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Upload\FileUploadService;

class CleanupTemporaryUploads extends Command
{
    protected $signature = 'uploads:cleanup';
    protected $description = 'Cleanup expired temporary uploads';

    public function __construct(
        private FileUploadService $uploadService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting cleanup of temporary uploads...');
        
        $result = $this->uploadService->cleanupExpiredUploads();
        
        $this->info("Deleted {$result['deleted']} expired uploads.");
        
        if (!empty($result['failed'])) {
            $this->error("Failed to delete " . count($result['failed']) . " uploads.");
            foreach ($result['failed'] as $failed) {
                $this->line(" - {$failed['temp_id']}: {$failed['error']}");
            }
        }
        
        $this->info('Cleanup completed.');
        
        return Command::SUCCESS;
    }
}