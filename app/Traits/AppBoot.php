<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Lightweight replacement for the vendor AppBoot trait.
 * This disables remote Envato checks and provides minimal
 * bootstrap helpers expected by the application.
 */
trait AppBoot
{
    // No-op in local replacement: always legal
    public function isLegal()
    {
        return true;
    }

    // Placeholder for showInstall used by FortifyServiceProvider
    public function showInstall()
    {
        // Intentionally left blank to avoid external checks during boot
        return true;
    }

    // Expose checkMigrateStatus wrapper (the app expects a function)
    public function checkMigrateStatus()
    {
        if (function_exists('check_migrate_status')) {
            return check_migrate_status();
        }

        return null;
    }
}
