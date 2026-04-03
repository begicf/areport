<?php

namespace App\Providers;

use App\Model\Taxonomy;
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
        //
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
