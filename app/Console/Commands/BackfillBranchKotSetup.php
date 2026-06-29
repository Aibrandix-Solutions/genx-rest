<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\KotPlace;
use App\Models\KotSetting;
use App\Models\MenuItem;
use App\Scopes\BranchScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillBranchKotSetup extends Command
{
    protected $signature = 'branches:backfill-kot-setup {--restaurant= : Limit to a restaurant ID} {--dry-run : Report only, do not write}';

    protected $description = 'Backfill missing kot_settings and default kitchen (kot_places) for branches';

    public function handle(): int
    {
        if (! Schema::hasTable('kot_settings') || ! Schema::hasTable('kot_places')) {
            $this->error('kot_settings or kot_places table does not exist.');

            return self::FAILURE;
        }

        $restaurantOption = $this->option('restaurant');
        $restaurantFilter = null;

        if ($restaurantOption !== null && $restaurantOption !== '') {
            if (! ctype_digit((string) $restaurantOption) || (int) $restaurantOption <= 0) {
                $this->error('Invalid --restaurant value. Provide a positive numeric restaurant ID.');

                return self::FAILURE;
            }

            $restaurantFilter = (int) $restaurantOption;
        }

        $dryRun = (bool) $this->option('dry-run');

        $query = Branch::query();

        if ($restaurantFilter) {
            $query->where('restaurant_id', $restaurantFilter);
        }

        $settingsCreated = 0;
        $kitchensCreated = 0;

        foreach ($query->cursor() as $branch) {
            $hasSettings = KotSetting::withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->id)
                ->exists();

            if (! $hasSettings) {
                $this->line("Branch #{$branch->id} ({$branch->name}): missing kot_settings");

                if (! $dryRun) {
                    $branch->generateKotSetting();
                }

                $settingsCreated++;
            }

            $defaultKotPlace = KotPlace::withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->id)
                ->where('is_default', true)
                ->first();

            if (! $defaultKotPlace) {
                $this->line("Branch #{$branch->id} ({$branch->name}): missing default kitchen");

                if (! $dryRun) {
                    $defaultKotPlace = KotPlace::withoutGlobalScope(BranchScope::class)->create([
                        'name' => 'Default Kitchen',
                        'branch_id' => $branch->id,
                        'printer_id' => null,
                        'type' => 'food',
                        'is_active' => true,
                        'is_default' => true,
                    ]);
                }

                $kitchensCreated++;
            }

            if ($defaultKotPlace && ! $dryRun) {
                MenuItem::withoutGlobalScope(BranchScope::class)
                    ->where('branch_id', $branch->id)
                    ->whereNull('kot_place_id')
                    ->update(['kot_place_id' => $defaultKotPlace->id]);
            }
        }

        $prefix = $dryRun ? '[dry-run] Would create' : 'Created';
        $this->info("{$prefix} {$settingsCreated} kot_settings row(s) and {$kitchensCreated} default kitchen(s).");

        return self::SUCCESS;
    }
}
