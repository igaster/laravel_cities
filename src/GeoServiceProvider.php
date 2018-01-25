<?php namespace Igaster\LaravelCities;

use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    public function register() {

    }

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
                \Igaster\LaravelCities\commands\seedGeoFile::class,
                \Igaster\LaravelCities\commands\seedJsonFile::class,
                \Igaster\LaravelCities\commands\BuildPplTree::class,
                \Igaster\LaravelCities\commands\Download::class,
            ]);
        }

    }

}