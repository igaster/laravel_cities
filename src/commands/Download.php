<?php

namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;

class Download extends Command
{
    public const ALL_COUNTRIES = 'all';

    protected $signature = 'geo:download {--countries='.self::ALL_COUNTRIES.'}';
    protected $description = 'Download a *.txt files from geonames.org By default it will download allcountries and hierarchy files';

    public function getFileNames() : array
    {
        $countries = $this->option('countries');

        $files = ['hierarchy.zip', 'admin1CodesASCII.txt'];

        if ($countries == self::ALL_COUNTRIES) {
            $files = ['allCountries.zip', 'hierarchy.zip'];
        } else {
            $countries = explode(',', $countries);        

            foreach ($countries as $country) {
                $files[] = "$country.zip";
            }
        }

        return $files;
    }

    public function handle()
    {
        foreach ($this->getFileNames() as $fileName) {
            $source = "http://download.geonames.org/export/dump/$fileName";
            $target = storage_path("geo/$fileName");
            $targetTxt = storage_path('geo/' . preg_replace('/\.zip/', '.txt', $fileName));

            $this->info(" Source file {$source}" . PHP_EOL . " Target file {$targetTxt}");

           if (! (file_exists($target) || file_exists($targetTxt))) {
                $this->info(" Downloading file {$fileName}");
                if (! copy($source, $target)) {
                    throw new \Exception("Failed to download the file $source");
                }
            }

            if (file_exists($target) && ! file_exists($targetTxt)) {
                if (preg_match('/\.zip/', $fileName)) {
                    $zip = new \ZipArchive;
                    $zip->open($target);
                    $zip->extractTo(dirname($target));
                    $zip->close();
                }
            }
        }
    }
}
