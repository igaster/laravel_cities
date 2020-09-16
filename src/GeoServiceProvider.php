<?php

namespace Igaster\LaravelCities;

use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load Routes
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->publishes([
            __DIR__ . '/vue' => resource_path('LaravelCities'),
        ], 'vue');

        $this->handleMigrations();
        $this->handleRoutes();
        $this->handleConsoleCommands();
    }

    /*--------------------------------------------------------------------------
    | Register Console Commands
    |--------------------------------------------------------------------------*/

    private function handleConsoleCommands()
    {
        // Register Console Commands
        if ($this->app->runningInConsole()) {

            $this->commands([
                \Igaster\LaravelCities\commands\seedGeoFile::class,
                \Igaster\LaravelCities\commands\seedJsonFile::class,
                \Igaster\LaravelCities\commands\BuildPplTree::class,
                \Igaster\LaravelCities\commands\Download::class,
                \Igaster\LaravelCities\commands\seedAlternateNames::class
            ]);

        }
    }

    /*--------------------------------------------------------------------------
    | Register Routes
    |--------------------------------------------------------------------------*/

    private function handleRoutes()
    {
        include __DIR__ . '/routes.php';
    }

    /*--------------------------------------------------------------------------
    | Database Migrations
    |--------------------------------------------------------------------------*/

    private function handleMigrations()
    {

        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        // Optional: Publish the migrations:
        $this->publishes([
            __DIR__ . '/migrations' => base_path('database/migrations'),
        ]);
    }

}
