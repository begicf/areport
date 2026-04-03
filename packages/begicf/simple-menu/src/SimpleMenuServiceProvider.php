<?php

namespace SimpleMenu;

use Illuminate\Support\ServiceProvider;


class SimpleMenuServiceProvider extends ServiceProvider
{


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/menu/components/'),
        ]);

        $this->loadViewComponentsAs('menu',[MenuComponent::class]);




    }
}
