<?php

namespace Modules\Hrm\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HrmServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Hrm';

    protected string $nameLower = 'hrm';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));

        $this->registerBladeComponents();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'Resources/lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'Resources/lang'));
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'Config/config.php') => config_path($this->nameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->name, 'Config/config.php'),
            $this->nameLower
        );
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $moduleViewPaths = array_map(function ($path) {
            return $path . '/modules/' . $this->nameLower;
        }, \Config::get('view.paths'));

        $existingViewPaths = array_values(array_filter(
            array_merge($moduleViewPaths, [$sourcePath]),
            fn ($path) => is_dir($path)
        ));

        $this->loadViewsFrom($existingViewPaths, $this->nameLower);
    }

    protected function registerBladeComponents(): void
    {
        $componentPath = module_path($this->name, 'Resources/views/components');

        if (!is_dir($componentPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($componentPath));

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($componentPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $componentName = str_replace(['.blade.php', DIRECTORY_SEPARATOR], ['', '.'], $relativePath);

            Blade::component($this->nameLower . '::components.' . $componentName, $this->nameLower . '-' . str_replace('.', '-', $componentName));
        }
    }
}
