<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hisabkitab:backup {--retention=7 : Days of daily backups to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a timestamped SQL backup of the HisabKitab database with retention pruning';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $fileName = "backup-hisabkitab-{$timestamp}.sql";
        $filePath = "{$backupDir}/{$fileName}";

        $this->info("Starting database backup for HisabKitab at: {$timestamp}");

        try {
            $this->performBackup($filePath);

            $fileSize = round(File::size($filePath) / 1024, 2);
            $this->info("Backup successfully written to: {$filePath} ({$fileSize} KB)");
            Log::info("Database backup created successfully: {$fileName} ({$fileSize} KB)");

            // Retention cleanup
            $retentionDays = (int) $this->option('retention');
            $this->pruneOldBackups($backupDir, $retentionDays);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            Log::error("Database backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Generate SQL backup file via PHP PDO fallback or mysqldump.
     */
    protected function performBackup(string $filePath): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            throw new \Exception("Cannot open file for writing: {$filePath}");
        }

        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- HisabKitab SaaS Automated Database Backup\n");
        fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
        fwrite($handle, "-- Database: {$database}\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $this->line("Dumping table: {$table}");

            // Structure
            $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createTableStmt['Create Table'] ?? '';

            fwrite($handle, "-- Table structure for `{$table}`\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, "{$createSql};\n\n");

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                fwrite($handle, "-- Data for `{$table}`\n");
                foreach ($rows as $row) {
                    $columns = array_map(fn($col) => "`{$col}`", array_keys($row));
                    $values = array_map(function ($val) use ($pdo) {
                        if (is_null($val)) {
                            return 'NULL';
                        }
                        return $pdo->quote($val);
                    }, array_values($row));

                    $insertSql = sprintf(
                        "INSERT INTO `%s` (%s) VALUES (%s);\n",
                        $table,
                        implode(', ', $columns),
                        implode(', ', $values)
                    );
                    fwrite($handle, $insertSql);
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Remove backups older than specified retention days.
     */
    protected function pruneOldBackups(string $backupDir, int $retentionDays): void
    {
        $files = File::files($backupDir);
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $prunedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql' && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $prunedCount++;
            }
        }

        if ($prunedCount > 0) {
            $this->info("Pruned {$prunedCount} old backup(s) older than {$retentionDays} days.");
            Log::info("Pruned {$prunedCount} old database backup(s).");
        }
    }
}

