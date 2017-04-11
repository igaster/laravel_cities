<?php namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;
use Igaster\LaravelCities\commands\helpers\geoItem;
use Igaster\LaravelCities\commands\helpers\geoCollection;


class seedJsonFile extends Command
{
    protected $signature = 'geo:json {file} {--append}';
    protected $description = 'Load a json file.';

    private $pdo;

    public function __construct() {
        parent::__construct();
        $this->pdo = \DB::connection()->getPdo(\PDO::FETCH_ASSOC);
        if (!\Schema::hasTable('geo'))
            return;

        $this->geoItems = new geoCollection();
    }

    public function handle() {
        $start = microtime(true);

        $fileName = $this->argument('file');
        $fileName = storage_path('geo/{$fileName}.json')
        $append =  $this->option('append');

        dd($fileName);

        $this->info(" Done</info>");
        $time_elapsed_secs = microtime(true) - $start;
        $this->info("Timing: $time_elapsed_secs sec</info>");
    }
}
