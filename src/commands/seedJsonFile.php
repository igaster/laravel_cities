<?php namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;
use Igaster\LaravelCities\commands\helpers\geoItem;
use Igaster\LaravelCities\commands\helpers\geoCollection;
use Igaster\LaravelCities\Geo;

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
        $fileName = storage_path("geo/{$fileName}.json");
        $append =  $this->option('append');

        $data = json_decode(file_get_contents($fileName), true);
        if($data === null){
            $this->error("Error decoding json file. Check for syntax errors.");
            exit();
        }

        $progressBar = new \Symfony\Component\Console\Helper\ProgressBar($this->output, count($data));
        $count = 0;
        foreach ($data as $item) {
            if (isset($item['id'])){
                $geo = Geo::updateOrCreate(['id' => $item['id']],$item);
            }
            $progressBar->setProgress($count++);
        }
        $progressBar->finish();
        $this->info(" Finished Processing $count items");
        $time_elapsed_secs = microtime(true) - $start;
        $this->info("Timing: $time_elapsed_secs sec</info>");
    }
}
