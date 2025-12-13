<?php

namespace Database\Seeders;

use App\Enums\PackageType;
use App\Models\GlobalCurrency;
use App\Models\Module;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currency = GlobalCurrency::first();

        if (!$currency) {
            return;
        }

        $currencyId = $currency->id;
        $moduleIds = Module::pluck('id')->toArray();

        $this->syncPackage(
            ['package_type' => PackageType::DEFAULT->value],
            [
                'package_name' => 'Default',
                'description' => 'Its a default package and cannot be deleted',
                'currency_id' => $currencyId,
                'monthly_status' => 0,
                'annual_status' => 0,
                'annual_price' => null,
                'monthly_price' => null,
                'price' => 0,
                'is_free' => 1,
                'billing_cycle' => 12,
                'sort_order' => 1,
                'is_private' => 0,
                'is_recommended' => 0,
                'package_type' => PackageType::DEFAULT,
            ],
            $moduleIds
        );

        $this->syncPackage(
            ['package_name' => 'Subscription Package'],
            [
                'package_name' => 'Subscription Package',
                'description' => 'This is a subscription package',
                'currency_id' => $currencyId,
                'monthly_status' => 1,
                'annual_status' => 1,
                'annual_price' => 100,
                'monthly_price' => 10,
                'price' => 0,
                'is_free' => 0,
                'billing_cycle' => 10,
                'sort_order' => 2,
                'is_private' => 0,
                'is_recommended' => 1,
                'package_type' => PackageType::STANDARD,
            ],
            $moduleIds
        );

        $this->syncPackage(
            ['package_type' => PackageType::LIFETIME->value],
            [
                'package_name' => 'Life Time',
                'description' => 'This is a lifetime access package',
                'currency_id' => $currencyId,
                'monthly_status' => 0,
                'annual_status' => 0,
                'annual_price' => null,
                'monthly_price' => null,
                'price' => 199,
                'is_free' => 0,
                'billing_cycle' => 0,
                'sort_order' => 3,
                'is_private' => 0,
                'is_recommended' => 1,
                'additional_features' => json_encode(Package::ADDITIONAL_FEATURES),
                'package_type' => PackageType::LIFETIME,
            ],
            $moduleIds
        );

        $this->syncPackage(
            ['package_name' => 'Private Package'],
            [
                'package_name' => 'Private Package',
                'description' => 'This is a private package',
                'price' => 0,
                'currency_id' => $currencyId,
                'monthly_status' => 1,
                'annual_status' => 1,
                'annual_price' => 50,
                'monthly_price' => 5,
                'is_free' => 0,
                'billing_cycle' => 12,
                'sort_order' => 4,
                'is_private' => 1,
                'is_recommended' => 0,
                'package_type' => PackageType::STANDARD,
            ],
            $moduleIds
        );

        $this->syncPackage(
            ['package_type' => PackageType::TRIAL->value],
            [
                'package_name' => 'Trial Package',
                'description' => 'This is a trial package',
                'currency_id' => $currencyId,
                'monthly_status' => 0,
                'annual_status' => 0,
                'annual_price' => null,
                'monthly_price' => null,
                'price' => 0,
                'is_free' => 1,
                'billing_cycle' => 0,
                'sort_order' => null,
                'is_private' => 0,
                'is_recommended' => 0,
                'package_type' => PackageType::TRIAL,
                'additional_features' => json_encode(Package::ADDITIONAL_FEATURES),
                'trial_days' => 30,
                'trial_status' => 1,
                'trial_notification_before_days' => 5,
                'trial_message' => '30 Days Free Trial',
            ],
            $moduleIds
        );
    }

    protected function syncPackage(array $lookup, array $data, array $moduleIds): void
    {
        $package = Package::updateOrCreate($lookup, $data);

        $package->modules()->sync($moduleIds);

        $this->cleanupDuplicates($lookup, $package->id);
    }

    protected function cleanupDuplicates(array $lookup, int $keepId): void
    {
        $duplicates = Package::where($lookup)
            ->where('id', '!=', $keepId)
            ->get();

        foreach ($duplicates as $duplicate) {
            Restaurant::where('package_id', $duplicate->id)->update(['package_id' => $keepId]);
            $duplicate->modules()->detach();
            $duplicate->delete();
    }
    }
}
