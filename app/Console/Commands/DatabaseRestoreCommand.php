<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'udhaarhisab:restore {file? : Name or path of the backup file to restore} {--force : Force restoration without interactive prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the UdhaarHisab database from a SQL backup file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        $fileArg = $this->argument('file');

        if ($fileArg) {
            $filePath = File::exists($fileArg) ? $fileArg : "{$backupDir}/{$fileArg}";
        } else {
            // Find most recent backup in storage/app/backups
            $files = File::files($backupDir);
            $sqlFiles = array_filter($files, fn($f) => $f->getExtension() === 'sql');

            if (empty($sqlFiles)) {
                $this->error("No backup files found in: {$backupDir}");
                return Command::FAILURE;
            }

            // Sort by modified time descending
            usort($sqlFiles, fn($a, $b) => $b->getMTime() - $a->getMTime());
            $filePath = $sqlFiles[0]->getPathname();
        }

        if (!File::exists($filePath)) {
            $this->error("Backup file not found at: {$filePath}");
            return Command::FAILURE;
        }

        $baseName = basename($filePath);
        $this->warn("Target Backup File: {$baseName}");

        if (!$this->option('force') && !$this->confirm("Are you sure you want to restore? This will overwrite existing database records!")) {
            $this->info("Restoration cancelled.");
            return Command::SUCCESS;
        }

        $this->info("Restoring database from: {$baseName}...");

        try {
            $sql = File::get($filePath);
            DB::unprepared($sql);

            $this->info("Database restoration completed successfully!");
            Log::info("Database restored successfully from: {$baseName}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Restoration failed: " . $e->getMessage());
            Log::error("Database restoration failed from {$baseName}: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

