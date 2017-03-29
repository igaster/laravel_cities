<?php namespace Igaster\LaravelCities;

use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        // Load Routes
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->publishes([
            __DIR__.'/vue' => resource_path('LaravelCities'),
        ], 'vue');


        // Register Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // \Igaster\LaravelCities\commands\truncTable::class,
                \Igaster\LaravelCities\commands\parseGeoFile::class,
            ]);
        }

    }

}