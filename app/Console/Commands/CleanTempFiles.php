<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanTempFiles extends Command
{
    protected $signature = 'clean:temp {--hours=24 : Delete files older than X hours}';
    protected $description = 'Clean temporary files from storage';

    public function handle()
    {
        $hours = $this->option('hours');
        $cutoff = now()->subHours($hours);
        
        $paths = [
            storage_path('app/temp'),
            storage_path('app/public/temp'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];
        
        $deletedFiles = 0;
        $deletedDirs = 0;
        
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $this->warn("Path does not exist: {$path}");
                continue;
            }
            
            $files = glob($path . '/*');
            
            foreach ($files as $file) {
                try {
                    if (filemtime($file) < $cutoff->timestamp) {
                        if (is_dir($file)) {
                            // Don't delete important directories
                            $dirName = basename($file);
                            if (!in_array($dirName, ['.', '..', 'packages', 'services.php', 'config.php'])) {
                                File::deleteDirectory($file);
                                $deletedDirs++;
                                $this->line("Deleted directory: {$file}");
                            }
                        } else {
                            // Don't delete important files
                            $fileName = basename($file);
                            if (!in_array($fileName, ['.gitignore', '.gitkeep', 'index.html'])) {
                                unlink($file);
                                $deletedFiles++;
                                $this->line("Deleted file: {$file}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to delete {$file}: " . $e->getMessage());
                }
            }
        }
        
        // Also clean Laravel's temp directory
        $tempPath = sys_get_temp_dir();
        $laravelFiles = glob($tempPath . '/laravel*');
        foreach ($laravelFiles as $file) {
            if (filemtime($file) < $cutoff->timestamp) {
                @unlink($file);
                $deletedFiles++;
            }
        }
        
        $total = $deletedFiles + $deletedDirs;
        
        if ($total > 0) {
            $this->info("✅ Cleaned {$total} items ({$deletedFiles} files, {$deletedDirs} directories) older than {$hours} hours.");
            Log::info('Temporary files cleaned', [
                'files' => $deletedFiles,
                'directories' => $deletedDirs,
                'hours' => $hours,
            ]);
        } else {
            $this->info("✅ No temporary files found older than {$hours} hours.");
        }
        
        return 0;
    }
}