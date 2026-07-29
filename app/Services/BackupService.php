<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupService
{
    protected string $backupPath = 'backups';

    public function createBackup(): string
    {
        $filename = 'backup-' . Carbon::now()->format('Y-m-d-His') . '.sql';
        $path = $this->backupPath . '/' . $filename;

        // Get database connection info
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // For SQLite (common in development)
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) {
                Storage::disk('local')->put($path, file_get_contents($dbPath));
                return $filename;
            }
            throw new \Exception('SQLite database file not found.');
        }

        // For MySQL
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            storage_path('app/' . $path)
        );

        // Make sure backup directory exists
        Storage::disk('local')->makeDirectory($this->backupPath);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Backup failed. Please check database configuration.');
        }

        return $filename;
    }

    public function restoreBackup(string $filename): bool
    {
        if (app()->isProduction() && !config('database.allow_restore', false)) {
            throw new \RuntimeException('Production restore is disabled. Set ALLOW_DATABASE_RESTORE=true only during an approved maintenance window.');
        }
        $this->validateFilename($filename);
        $path = $this->backupPath . '/' . $filename;

        if (!Storage::disk('local')->exists($path)) {
            throw new \Exception('Backup file not found.');
        }

        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            $backupContent = Storage::disk('local')->get($path);
            file_put_contents($dbPath, $backupContent);
            return true;
        }

        // For MySQL
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            storage_path('app/' . $path)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }

    public function getBackupList(): array
    {
        $files = Storage::disk('local')->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => Storage::disk('local')->size($file),
                'created_at' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
            ];
        }

        usort($backups, fn($a, $b) => $b['created_at']->timestamp - $a['created_at']->timestamp);

        return $backups;
    }

    public function deleteBackup(string $filename): bool
    {
        $this->validateFilename($filename);
        $path = $this->backupPath . '/' . $filename;
        return Storage::disk('local')->delete($path);
    }

    public function downloadBackup(string $filename)
    {
        $this->validateFilename($filename);
        $path = $this->backupPath . '/' . $filename;

        if (!Storage::disk('local')->exists($path)) {
            throw new \Exception('Backup file not found.');
        }

        return Storage::disk('local')->download($path, $filename);
    }

    private function validateFilename(string $filename): void
    {
        if (!preg_match('/\Abackup-\d{4}-\d{2}-\d{2}-\d{6}\.(sql|sqlite)\z/', $filename)) {
            throw new \InvalidArgumentException('Invalid backup filename.');
        }
    }
}
