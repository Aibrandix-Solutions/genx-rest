<?php

use App\Models\EmailSetting;
use App\Models\GlobalSetting;
use App\Models\LanguageSetting;
use App\Helper\Files;
use App\Models\Package;
use App\Models\PaymentGatewayCredential;
use App\Models\PusherSetting;
use App\Models\Restaurant;
use App\Models\StorageSetting;
use App\Models\SuperadminPaymentGateway;
use App\Models\GlobalCurrency;
use App\Models\Currency;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use App\Models\OrderNumberSetting;
use Intervention\Image\Facades\Image;


if (!function_exists('user')) {

    /**
     * Return current logged-in user
     */
    function user()
    {
        if (session()->has('user')) {
            return session('user');
        }


        session(['user' => auth()->user()]);

        return session('user');
    }
}


function customer()
{
    if (session()->has('customer')) {
        return session('customer');
    }

    return null;
}

function restaurant()
{
    if (session()->has('restaurant')) {
        return session('restaurant');
    }

    if (user()) {
        if (user()->restaurant_id) {
            session(['restaurant' => Restaurant::find(user()->restaurant_id)]);
            return session('restaurant');
        }
    }

    // session(['restaurant' => Restaurant::first()]); // Used in Non-saas

    // return session('restaurant');  // Used in Non-saas
    return false;  // Used in Saas

}

function shop($hash = null)
{
    if (session()->has('shop')) {
        return session('shop');
    }

    if (!is_null($hash)) {
        session(['shop' => Restaurant::where('hash', $hash)->first()]);
        return session('shop');
    }

    return false;  // Used in Saas

}

function branch()
{
    if (session()->has('branch')) {
        return session('branch');
    }

    if (restaurant()) {
        session(['branch' => user()->branch ?? restaurant()->branches->first()]);
        return session('branch');
    }

    return false;
}

function shop_branch()
{
    if (session()->has('shop_branch')) {
        return session('shop_branch');
    }

    if (shop()) {
        session(['shop_branch' => shop()->branches->first()]);
        return session('shop_branch');
    }

    return false;
}

if (!function_exists('default_phone_code')) {
    /**
     * Get the default phone code for the system
     * Returns +94 (Sri Lanka) as the default country code
     * 
     * @return string
     */
    function default_phone_code()
    {
        return '94';
    }
}

function currency()
{
    if (session()->has('currency')) {
        return session('currency');
    }

    if (restaurant()) {
        session(['currency' => restaurant()->currency->currency_symbol]);

        return session('currency');
    }

    return false;
}

function timezone()
{
    if (session()->has('timezone')) {
        return session('timezone');
    }

    if (restaurant()) {
        session(['timezone' => restaurant()->timezone]);
        return session('timezone');
    }

    if (shop()) {
        $shopTz = shop()->timezone ?? null;
        if (!empty($shopTz)) {
            session(['timezone' => $shopTz]);

            return session('timezone');
        }
    }

    // For superadmin, use global setting timezone
    if (user() && is_null(user()->restaurant_id)) {
        $globalTimezone = global_setting()->timezone ?? config('app.timezone', 'Asia/Colombo');
        session(['timezone' => $globalTimezone]);
        return session('timezone');
    }

    return config('app.timezone', 'Asia/Colombo');
}

function paymentGateway()
{
    if (session()->has('paymentGateway')) {
        return session('paymentGateway');
    }

    if (shop()) {
        $payment = PaymentGatewayCredential::where('restaurant_id', shop()->id)->first();

        session(['paymentGateway' => $payment]);

        return session('paymentGateway');
    }

    return false;
}

if (!function_exists('check_migrate_status')) {

    // @codingStandardsIgnoreLine
    function check_migrate_status()
    {

        // Do NOT run migrations or heavy DB operations during web requests.
        // Running migrations during HTTP requests can cause race conditions,
        // duplicate table errors and admin-side 500s. Only allow the
        // migrate check/commands when running in the console (artisan).
        // if (!app()->runningInConsole()) {
        //     // mark that we've skipped the migrate check for this session
        //     if (!session()->has('check_migrate_status')) {
        //         session(['check_migrate_status' => 'skipped_in_http']);
        //     }

        //     return session('check_migrate_status');
        // }

        if (!session()->has('check_migrate_status')) {
            session(['check_migrate_status' => 'skipped_in_http']);
        }

        return session('check_migrate_status');
    }
}

if (!function_exists('role_permissions')) {

    function role_permissions()
    {
        if (session()->has('role_permissions')) {
            return session('role_permissions');
        }

        if (is_null(user())) {
            return [];
        }

        $roleID = user()->roles->first()->id;
        $permissions = Role::where('id', $roleID)->first()->permissions->pluck('name')->toArray();

        session(['role_permissions' => $permissions]);
        return  session('role_permissions');
    }
}

if (!function_exists('user_can')) {

    function user_can($permission)
    {
        if (user() && method_exists(user(), 'can')) {
            return user()->can($permission);
        }

        if (is_null(role_permissions())) {
            $rolePermissions = [];
        } else {
            $rolePermissions = role_permissions();
        }

        return in_array($permission, $rolePermissions);
    }
}

if (!function_exists('restaurant_modules')) {
    function restaurant_modules($restaurant = null): array
    {
        $restaurant = $restaurant ?: restaurant();

        if (!$restaurant) {
            return [];
        }

        $filterModules = static function (array $modules) {
            if (!class_exists(\Nwidart\Modules\Facades\Module::class)) {
                return $modules;
            }

            return array_values(array_filter($modules, function ($moduleName) {
                if (Module::has($moduleName)) {
                    return Module::isEnabled($moduleName);
                }

                return true;
            }));
        };

        $user = user();

        if (!$user || (is_null($user->restaurant_id) && is_null($user->branch_id))) {
            return [];
        }

        $restaurantModel = Restaurant::with('package.modules')->find($restaurant->id);

        if (!$restaurantModel || !$restaurantModel->package) {
            return [];
        }

        session(['restaurant' => $restaurantModel]);

        $modulesStatusPath = storage_path('app/modules_statuses.json');
        $modulesStatusVersion = file_exists($modulesStatusPath) ? md5_file($modulesStatusPath) : 'no-module-status';
        $packageVersion = optional($restaurantModel->package->updated_at)->timestamp ?? 'no-package-version';

        $cacheKey = implode('_', [
            'restaurant_modules',
            $restaurantModel->id,
            $restaurantModel->package_id,
            $packageVersion,
            $modulesStatusVersion,
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($restaurantModel, $filterModules) {
            $packageModules = $restaurantModel->package->modules->pluck('name')->toArray();
            $additionalFeatures = json_decode($restaurantModel->package->additional_features ?? '[]', true);
            $allModules = array_unique(array_merge($packageModules, $additionalFeatures));

            return $filterModules($allModules);
        });
    }
}


if (!function_exists('global_setting')) {

    // @codingStandardsIgnoreLine
    function global_setting()
    {

        if (cache()->has('global_setting')) {
            return cache('global_setting');
        }

        cache(['global_setting' => GlobalSetting::first()]);

        return cache('global_setting');
    }
}

if (!function_exists('restaurantOrGlobalSetting')) {

    function restaurantOrGlobalSetting()
    {
        if (user()) {

            if (user()->restaurant_id) {
                return restaurant();
            }
        }

        return global_setting();
    }
}

if (!function_exists('branches')) {

    function branches()
    {

        if (session()->has('branches')) {
            return session('branches');
        }

        if (restaurant()) {
            return session(['branches' => restaurant()->branches]);
        }

        return false;
    }
}

if (!function_exists('isRtl')) {

    function isRtl()
    {

        if (session()->has('isRtl')) {
            return session('isRtl');
        }

        if (user()) {
            $language = LanguageSetting::where('language_code', auth()->user()->locale)->first();
            $isRtl = ($language->is_rtl == 1);
            session(['isRtl' => $isRtl]);
        }

        return false;
    }
}

if (!function_exists('languages')) {

    function languages()
    {

        if (cache()->has('languages')) {
            return cache('languages');
        }

        $languages = LanguageSetting::where('active', 1)->get();
        cache(['languages' => $languages]);

        return cache('languages');
    }
}

if (!function_exists('asset_url_local_s3')) {

    // @codingStandardsIgnoreLine
    function asset_url_local_s3($path)
    {
        if (in_array(config('filesystems.default'), StorageSetting::S3_COMPATIBLE_STORAGE)) {
            // Check if the URL is already cached
            if (Cache::has(config('filesystems.default') . '-' . $path)) {
                $temporaryUrl = Cache::get(config('filesystems.default') . '-' . $path);
            } else {
                // Generate a new temporary URL and cache it
                $temporaryUrl = Storage::disk(config('filesystems.default'))->temporaryUrl($path, now()->addMinutes(StorageSetting::HASH_TEMP_FILE_TIME));
                Cache::put(config('filesystems.default') . '-' . $path, $temporaryUrl, StorageSetting::HASH_TEMP_FILE_TIME * 60);
            }

            return $temporaryUrl;
        }

        $path = Files::UPLOAD_FOLDER . '/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;
    }
}

if (!function_exists('download_local_s3')) {

    // @codingStandardsIgnoreLine
    function download_local_s3($file, $path)
    {

        if (in_array(config('filesystems.default'), StorageSetting::S3_COMPATIBLE_STORAGE)) {
            return Storage::disk(config('filesystems.default'))->download($path, basename($file->filename));
        }

        $path = Files::UPLOAD_FOLDER . '/' . $path;
        $ext = pathinfo($file->filename, PATHINFO_EXTENSION);

        $filename = $file->name ? $file->name . '.' . $ext : $file->filename;
        try {
            return response()->download($path, $filename);
        } catch (\Exception $e) {
            return response()->view('errors.file_not_found', ['message' => $e->getMessage()], 404);
        }
    }
}


if (!function_exists('asset_url')) {

    // @codingStandardsIgnoreLine
    function asset_url($path)
    {
        $path = \App\Helper\Files::UPLOAD_FOLDER . '/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;
    }
}

if (!function_exists('getDomain')) {

    function getDomain($host = false)
    {
        if (!$host) {
            $host = $_SERVER['SERVER_NAME'] ?? 'tabletrack.test';
        }

        $shortDomain = config('app.short_domain_name');
        $dotCount = ($shortDomain === true) ? 2 : 1;

        $myHost = strtolower(trim($host));
        $count = substr_count($myHost, '.');

        if (!is_null(config('app.main_domain_name'))) {
            return config('app.main_domain_name');
        }

        if ($count === $dotCount || $count === 1) {
            return $myHost;
        }

        $myHost = explode('.', $myHost, 2);

        return end($myHost);
    }
}

if (!function_exists('getDomainSpecificUrl')) {

    function getDomainSpecificUrl($url, $restaurant = null)
    {
        // Check if Subdomain module exist
        if (!module_enabled('Subdomain')) {
            return $url;
        }

        config(['app.url' => config('app.main_app_url')]);

        // If restaurant specific
        if ($restaurant) {
            $restaurantUrl = (config('app.redirect_https') ? 'https' : 'http') . '://' . $restaurant->sub_domain;

            config(['app.url' => $restaurantUrl]);
            // Removed Illuminate\Support\Facades\URL::forceRootUrl($companyUrl);

            if (Str::contains($url, $restaurant->sub_domain)) {
                return $url;
            }

            $url = str_replace(request()->getHost(), $restaurant->sub_domain, $url);
            $url = str_replace('www.', '', $url);

            // Replace https to http for sub-domain to
            if (!config('app.redirect_https')) {
                return str_replace('https', 'http', $url);
            }

            return $url;
        }

        // Removed config(['app.url' => $url]);
        // Comment      \Illuminate\Support\Facades\URL::forceRootUrl($url);
        // If there is no restaurant and url has login means
        // New superadmin is created
        return str_replace('login', 'super-admin-login', $url);
    }
}


function module_enabled($moduleName)
{
    return Module::has($moduleName) && Module::isEnabled($moduleName);
}

if (!function_exists('package')) {

    function package()
    {

        if (cache()->has('package')) {
            return cache('package');
        }

        $package = Package::first();

        cache(['package' => $package]);

        return cache('package');
    }
}

function superadminPaymentGateway()
{
    if (cache()->has('superadminPaymentGateway')) {
        return cache('superadminPaymentGateway');
    }

    $payment = SuperadminPaymentGateway::first();

    cache(['superadminPaymentGateway' => $payment]);

    return cache('superadminPaymentGateway');
}


function pusherSettings()
{


    if (cache()->has('pusherSettings')) {
        return cache('pusherSettings');
    }

    $setting = PusherSetting::first();

    cache(['pusherSettings' => $setting]);

    return cache('pusherSettings');
}

if (!function_exists('clearRestaurantModulesCache')) {

    function clearRestaurantModulesCache($restaurantId)
    {
        if (is_null($restaurantId)) {
            return true;
        }

        cache()->forget('restaurant_modules_' . $restaurantId);
    }
}

if (!function_exists('currency_format_setting')) {

    // @codingStandardsIgnoreLine
    function currency_format_setting($currencyId = null)
    {
        if (!session()->has('currency_format_setting' . $currencyId) || (is_null($currencyId) && restaurant())) {
            if ($currencyId == null && restaurant()) {
                $setting = restaurant()->load('currency')->currency;
            } else {
                $setting = Currency::where('id', $currencyId)->first();
            }
            session(['currency_format_setting' . $currencyId => $setting]);
        }

        return session('currency_format_setting' . $currencyId);
    }
}

if (!function_exists('currency_format')) {

    // @codingStandardsIgnoreLine
    function currency_format($amount, $currencyId = null, $showSymbol = true, $showCode = false)
    {
        $formats = currency_format_setting($currencyId);

        $settings = $formats->restaurant ?? Restaurant::find($formats->restaurant_id);

        if ($showCode) {
            $currency_symbol = $formats->currency_code ?? '';
        }
        else{
            if (!$showSymbol) {
                $currency_symbol = '';
            } else {
                $settings = $formats->restaurant ?? Restaurant::find($formats->restaurant_id);
                $currency_symbol = $currencyId == null ? $settings->currency->currency_symbol :
$formats->currency_symbol;
            }
        }


        $currency_position = $formats->currency_position ?? 'left';
        $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
        $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
        $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

        $amount = number_format(floatval($amount), $no_of_decimal, $decimal_separator, $thousand_separator);

        $amount = match ($currency_position) {
            'right' => $amount . $currency_symbol,
            'left_with_space' => $currency_symbol . ' ' . $amount,
            'right_with_space' => $amount . ' ' . $currency_symbol,
            default => $currency_symbol . $amount,
        };

        return $amount;
    }
}

if (!function_exists('currency_format_for_receipt_item')) {

    // @codingStandardsIgnoreLine
    // Format currency for receipt items - respects show_currency_prefix setting
    function currency_format_for_receipt_item($amount, $currencyId = null, $showCode = false)
    {
        $formats = currency_format_setting($currencyId);
        $settings = $formats->restaurant ?? Restaurant::find($formats->restaurant_id);

        // Check if currency prefix should be hidden for ITEMS based on receipt setting
        $currentRestaurant = null;
        try {
            $currentRestaurant = restaurant();
        } catch (\Exception $e) {
            // If restaurant() helper fails, use the restaurant from currency
        }
        
        $restaurantToCheck = $currentRestaurant ?? $settings;
        
        // Load the receiptSetting relationship if not already loaded
        if ($restaurantToCheck && !$restaurantToCheck->relationLoaded('receiptSetting')) {
            $restaurantToCheck->load('receiptSetting');
        }
        
        $receiptSetting = $restaurantToCheck?->receiptSetting;
        $hideCurrencyPrefix = $receiptSetting && isset($receiptSetting->show_currency_prefix) && !$receiptSetting->show_currency_prefix;

        if ($showCode) {
            $currency_symbol = $formats->currency_code ?? '';
        }
        else{
            if ($hideCurrencyPrefix) {
                $currency_symbol = '';
            } else {
                $currency_symbol = $currencyId == null ? $settings->currency->currency_symbol :
$formats->currency_symbol;
            }
        }

        $currency_position = $formats->currency_position ?? 'left';
        $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
        $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
        $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

        $amount = number_format(floatval($amount), $no_of_decimal, $decimal_separator, $thousand_separator);

        $amount = match ($currency_position) {
            'right' => $amount . $currency_symbol,
            'left_with_space' => $currency_symbol . ' ' . $amount,
            'right_with_space' => $amount . ' ' . $currency_symbol,
            default => $currency_symbol . $amount,
        };

        return $amount;
    }
}


if (!function_exists('global_currency_format_setting')) {

    // @codingStandardsIgnoreLine
    function global_currency_format_setting($currencyId = null)
    {
        if (!session()->has('global_currency_format_setting' . $currencyId)) {
            $setting = $currencyId == null ? GlobalCurrency::first() : GlobalCurrency::where('id', $currencyId)->first();
            session(['global_currency_format_setting' . $currencyId => $setting]);
        }

        return session('global_currency_format_setting' . $currencyId);
    }
}

if (!function_exists('global_currency_format')) {

    // @codingStandardsIgnoreLine
    function global_currency_format($amount, $currencyId = null, $showSymbol = true)
    {
        $formats = global_currency_format_setting($currencyId);


        if (!$showSymbol) {
            $currency_symbol = '';
        } else {
            $currency_symbol = $formats->currency_symbol;
        }

        $currency_position = $formats->currency_position;
        $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
        $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
        $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

        $amount = number_format($amount, $no_of_decimal, $decimal_separator, $thousand_separator);

        $amount = match ($currency_position) {
            'right' => $amount . $currency_symbol,
            'left_with_space' => $currency_symbol . ' ' . $amount,
            'right_with_space' => $amount . ' ' . $currency_symbol,
            default => $currency_symbol . $amount,
        };

        return $amount;
    }
}

if (!function_exists('smtp_setting')) {

    // @codingStandardsIgnoreLine
    function smtp_setting()
    {
        if (!session()->has('smtp_setting')) {
            session(['smtp_setting' => EmailSetting::first()]);
        }

        return session('smtp_setting');
    }
}

if (!function_exists('custom_module_plugins')) {

    // @codingStandardsIgnoreLine
    function custom_module_plugins()
    {

        if (!cache()->has('custom_module_plugins')) {
            $plugins = \Nwidart\Modules\Facades\Module::allEnabled();
            cache(['custom_module_plugins' => array_keys($plugins)]);
        }

        return cache('custom_module_plugins');
    }
}

if (!function_exists('isOrderPrefixEnabled')) {

    /**
     * Check if order prefix feature is enabled for the given branch
     */
    function isOrderPrefixEnabled($branch = null)
    {
        if (!$branch) {
            $branch = branch();
        }

        if (!$branch) {
            return false;
        }

        $settings = OrderNumberSetting::where('branch_id', $branch->id)->first();
        return $settings && $settings->enable_feature;
    }
}
