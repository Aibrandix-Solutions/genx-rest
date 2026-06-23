<?php

namespace Modules\Hotel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ActivateModuleCommand extends Command
{
    protected $signature = 'hotel:activate';

    protected $description = 'Migrate and seed Hotel module permissions and order types';

    public function handle(): int
    {
        try {
            Artisan::call('module:migrate', ['module' => 'Hotel']);
        } catch (\Exception $e) {
            // Log the exception and continue if it's a known "already migrated" scenario
            if (!str_contains($e->getMessage(), 'already exists') && 
                !str_contains($e->getMessage(), 'Nothing to migrate')) {
                $this->error('Migration failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        Artisan::call('module:seed', [
            'module' => 'Hotel',
            '--force' => true,
        ]);

        $this->info('Hotel module activated (migrations + seeders).');

        return self::SUCCESS;
    }
}
