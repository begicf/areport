<?php

namespace App\Providers;

use App\Model\Taxonomy;
use App\Support\InstanceHooks;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(InstanceHooks::class);

        foreach (config('areport.plugin_paths', []) as $dir) {
            $this->loadPlugin($dir);
        }

        foreach (config('areport.extension_providers', []) as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Load a private plugin that sits next to the application (not a Composer
     * dependency): its PSR-4 namespaces and its Laravel providers, as package
     * discovery would. Nothing happens when the folder is not there.
     */
    private function loadPlugin(string $dir): void
    {
        $dir = str_starts_with($dir, DIRECTORY_SEPARATOR) ? $dir : base_path($dir);
        $meta = is_file($dir.'/composer.json') ? json_decode((string) file_get_contents($dir.'/composer.json'), true) : null;
        if (! is_array($meta)) {
            return;
        }
        $loader = require base_path('vendor/autoload.php');
        foreach ($meta['autoload']['psr-4'] ?? [] as $namespace => $path) {
            $loader->addPsr4($namespace, $dir.'/'.trim((string) $path, '/'));
        }
        foreach ($meta['extra']['laravel']['providers'] ?? [] as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            $activeTaxonomy = null;

            try {
                if (Schema::hasTable('taxonomies')) {
                    $activeTaxonomy = Taxonomy::query()->where('active', true)->first();
                }
            } catch (Throwable $exception) {
                $activeTaxonomy = null;
            }

            $view->with('activeTaxonomy', $activeTaxonomy);
        });
    }
}
