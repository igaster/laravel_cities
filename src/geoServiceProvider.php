<?php namespace igaster\laravel_cities;

use Illuminate\Support\ServiceProvider;

class geoServiceProvider extends ServiceProvider
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
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Load Views
        $this->loadViewsFrom(__DIR__.'/views', 'laravel_cities');

        // Register Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \igaster\laravel_cities\commands\truncTable::class,
                \igaster\laravel_cities\commands\parseGeoFile::class,
            ]);
        }

    }

}