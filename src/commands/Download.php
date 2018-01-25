<?php

namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;

class Download extends Command
{
    protected $signature = 'geo:download {--countries=}';
    protected $description = 'Download a *.txt files from geonames.org';

    public function handle()
    {
        $countries = $this->option('countries');
        $countries = explode(',', $countries);

        $files = ['hierarchy.zip', 'admin1CodesASCII.txt'];

        foreach ($countries as $country) {
            $files[] = "$country.zip";
        }

        foreach ($files as $fileName) {
            $source = "http://download.geonames.org/export/dump/$fileName";
            $target = storage_path("geo/$fileName");
            $targetTxt = storage_path("geo/" . preg_replace('/\.zip/', '.txt', $fileName));

            if (!(file_exists($target) || file_exists($targetTxt))) {
                if (!copy($source, $target)) {
                    throw new \Exception("Failed to download the file $remotePath");
                }
            }

            if (file_exists($target) && !file_exists($targetTxt)) {
                if (preg_match('/\.zip/', $fileName)) {
                    $zip = new \ZipArchive;
                    $zipped = $zip->open($target);
                    $extract = $zip->extractTo(dirname($target));
                    $zip->close();
                }
            }
        }
    }

}