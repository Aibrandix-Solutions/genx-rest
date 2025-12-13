<?php

namespace Modules\Backup\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Backup\Models\DatabaseBackupSetting;
use App\Models\StorageSetting;

class DiagnoseBackupIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose potential issues with the backup system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Backup System Diagnostic');
        $this->info('========================================');
        $this->newLine();

        $issues = [];
        $warnings = [];
        $checks = [];

        // Check 1: Backup Directory
        $this->info('1. Checking Backup Directory...');
        $backupDir = storage_path('app/backups');
        $check = $this->checkBackupDirectory($backupDir);
        $checks[] = $check;
        if (!$check['passed']) {
            $issues[] = $check['message'];
        } elseif (!empty($check['warning'])) {
            $warnings[] = $check['warning'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '✗ Failed'));
        $this->newLine();

        // Check 2: Database Connection
        $this->info('2. Checking Database Connection...');
        $check = $this->checkDatabaseConnection();
        $checks[] = $check;
        if (!$check['passed']) {
            $issues[] = $check['message'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '✗ Failed'));
        $this->newLine();

        // Check 3: MySQL Tools
        $this->info('3. Checking MySQL Tools...');
        $check = $this->checkMysqlTools();
        $checks[] = $check;
        if (!$check['passed']) {
            $warnings[] = $check['message']; // Warning, not critical
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '⚠ Warning'));
        $this->newLine();

        // Check 4: Disk Space
        $this->info('4. Checking Disk Space...');
        $check = $this->checkDiskSpace($backupDir);
        $checks[] = $check;
        if (!$check['passed']) {
            $issues[] = $check['message'];
        } elseif (!empty($check['warning'])) {
            $warnings[] = $check['warning'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '✗ Failed'));
        $this->newLine();

        // Check 5: Backup Settings
        $this->info('5. Checking Backup Settings...');
        $check = $this->checkBackupSettings();
        $checks[] = $check;
        if (!$check['passed']) {
            $warnings[] = $check['message'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '⚠ Warning'));
        $this->newLine();

        // Check 6: Cloud Storage (if configured)
        $this->info('6. Checking Cloud Storage Configuration...');
        $check = $this->checkCloudStorage();
        $checks[] = $check;
        if (!$check['passed'] && !empty($check['message'])) {
            $warnings[] = $check['message'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '⚠ ' . ($check['message'] ?: 'Not Configured')));
        $this->newLine();

        // Check 7: Recent Backups
        $this->info('7. Checking Recent Backup Status...');
        $check = $this->checkRecentBackups();
        $checks[] = $check;
        if (!$check['passed']) {
            $warnings[] = $check['message'];
        }
        $this->line("   Status: " . ($check['passed'] ? '✓ Passed' : '⚠ Warning'));
        $this->newLine();

        // Summary
        $this->info('========================================');
        $this->info('Diagnostic Summary');
        $this->info('========================================');
        $this->newLine();

        $passedChecks = count(array_filter($checks, fn($c) => $c['passed']));
        $totalChecks = count($checks);

        if (empty($issues) && empty($warnings)) {
            $this->info("✓ All checks passed! ({$passedChecks}/{$totalChecks})");
            $this->info("Your backup system is properly configured.");
        } else {
            if (!empty($issues)) {
                $this->error("✗ Critical Issues Found ({$passedChecks}/{$totalChecks} checks passed):");
                foreach ($issues as $issue) {
                    $this->line("  - {$issue}");
                }
                $this->newLine();
            }

            if (!empty($warnings)) {
                $this->warn("⚠ Warnings:");
                foreach ($warnings as $warning) {
                    $this->line("  - {$warning}");
                }
                $this->newLine();
            }
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Check backup directory
     */
    private function checkBackupDirectory($path)
    {
        if (!file_exists($path)) {
            return [
                'passed' => false,
                'message' => "Backup directory does not exist: {$path}. It will be created automatically on next backup.",
            ];
        }

        if (!is_dir($path)) {
            return [
                'passed' => false,
                'message' => "Backup path exists but is not a directory: {$path}",
            ];
        }

        if (!is_readable($path)) {
            return [
                'passed' => false,
                'message' => "Backup directory is not readable: {$path}. Check permissions (chmod 755).",
            ];
        }

        if (!is_writable($path)) {
            return [
                'passed' => false,
                'message' => "Backup directory is not writable: {$path}. Check permissions (chmod 755).",
            ];
        }

        return [
            'passed' => true,
            'message' => "Backup directory is accessible and writable.",
        ];
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            $driver = config('database.default');
            $config = config("database.connections.{$driver}");
            
            return [
                'passed' => true,
                'message' => "Database connection successful. Driver: {$driver}, Database: {$config['database']}",
            ];
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => "Database connection failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check MySQL tools availability
     */
    private function checkMysqlTools()
    {
        $mysqldump = $this->findCommand('mysqldump');
        
        if (!$mysqldump) {
            return [
                'passed' => false,
                'message' => "mysqldump not found in PATH. The system will use Laravel-native backup method instead.",
            ];
        }

        return [
            'passed' => true,
            'message' => "mysqldump found at: {$mysqldump}",
        ];
    }

    /**
     * Find command in PATH
     */
    private function findCommand($command)
    {
        $paths = [
            '/usr/bin',
            '/usr/local/bin',
            '/opt/homebrew/bin',
            'C:\xampp\mysql\bin',
            'C:\Program Files\MySQL\MySQL Server 8.0\bin',
            'C:\wamp64\bin\mysql\mysql8.0.27\bin',
        ];

        // Check if command is in PATH
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("where {$command} 2>nul");
        } else {
            $output = shell_exec("which {$command} 2>/dev/null");
        }

        if ($output && !empty(trim($output))) {
            return trim(explode("\n", $output)[0]);
        }

        // Check common paths
        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $command . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace($path)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: Get free space
            $drive = substr($path, 0, 2);
            $freeBytes = disk_free_space($drive);
        } else {
            $freeBytes = disk_free_space($path);
        }

        if ($freeBytes === false) {
            return [
                'passed' => true, // Can't determine, assume OK
                'warning' => 'Could not determine available disk space.',
            ];
        }

        $freeMB = round($freeBytes / 1024 / 1024, 2);
        $freeGB = round($freeMB / 1024, 2);

        if ($freeMB < 100) {
            return [
                'passed' => false,
                'message' => "Very low disk space: {$freeMB} MB free. Backups may fail.",
            ];
        }

        if ($freeMB < 500) {
            return [
                'passed' => true,
                'warning' => "Low disk space: {$freeMB} MB ({$freeGB} GB) free. Consider cleaning up old backups.",
            ];
        }

        return [
            'passed' => true,
            'message' => "Sufficient disk space available: {$freeGB} GB free.",
        ];
    }

    /**
     * Check backup settings
     */
    private function checkBackupSettings()
    {
        try {
            $settings = DatabaseBackupSetting::getSettings();
            
            if (!$settings->is_enabled) {
                return [
                    'passed' => true, // Not an error, just informational
                    'message' => 'Scheduled backups are disabled.',
                ];
            }

            $message = "Scheduled backups enabled. Frequency: {$settings->frequency}, Time: {$settings->backup_time}";
            
            return [
                'passed' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => "Error reading backup settings: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cloud storage configuration
     */
    private function checkCloudStorage()
    {
        try {
            $settings = DatabaseBackupSetting::getSettings();
            
            if ($settings->storage_location === 'local') {
                return [
                    'passed' => true,
                    'message' => 'Using local storage (cloud storage not configured).',
                ];
            }

            // Check if cloud storage is configured
            $storageSetting = StorageSetting::where('status', 'enabled')->first();
            
            if (!$storageSetting) {
                return [
                    'passed' => false,
                    'message' => 'Cloud storage selected but no storage setting is enabled.',
                ];
            }

            // Test connection
            try {
                $disk = Storage::disk($storageSetting->filesystem);
                $disk->put('backup-test.txt', 'test');
                $disk->delete('backup-test.txt');
                
                return [
                    'passed' => true,
                    'message' => "Cloud storage configured: {$storageSetting->filesystem}",
                ];
            } catch (\Exception $e) {
                return [
                    'passed' => false,
                    'message' => "Cloud storage connection failed: " . $e->getMessage(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'passed' => true, // Not critical
                'message' => 'Could not check cloud storage: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check recent backups
     */
    private function checkRecentBackups()
    {
        try {
            $backups = \Modules\Backup\Models\DatabaseBackup::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($backups->isEmpty()) {
                return [
                    'passed' => true,
                    'message' => 'No backups found yet.',
                ];
            }

            $failed = $backups->where('status', 'failed')->count();
            $completed = $backups->where('status', 'completed')->count();
            $inProgress = $backups->where('status', 'in_progress')->count();

            if ($failed > 0) {
                return [
                    'passed' => false,
                    'message' => "{$failed} failed backup(s) found in recent backups. Check error messages for details.",
                ];
            }

            if ($inProgress > 0) {
                return [
                    'passed' => true,
                    'warning' => "{$inProgress} backup(s) currently in progress.",
                ];
            }

            return [
                'passed' => true,
                'message' => "Recent backups look good. {$completed} completed backup(s) found.",
            ];
        } catch (\Exception $e) {
            return [
                'passed' => true, // Not critical
                'message' => 'Could not check recent backups: ' . $e->getMessage(),
            ];
        }
    }
}

