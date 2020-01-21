<?php

namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BuildPplTree extends Command
{
    protected $signature = 'geo:build-ppl-tree {--countries=}';
    protected $description = 'Build a PPL* hierarchy-ppl-*.txt from admin1CodesASCII.txt';

    public function handle()
    {
        $countries = $this->option('countries');
        $countries = explode(',', $countries);

        try {
            $this->downloadAdmin1CodesASCIIIfNotExists();
            foreach ($countries as $country) {
                //$this->determinePplParentId($country);
                $this->buildPplHierarchy($country);
                $this->mergeHierarchies($country);
            }

            //$this->mergeAllHierarchies($countries);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function downloadAdmin1CodesASCIIIfNotExists()
    {
        $fileName = 'admin1CodesASCII.txt';
        $localPath = storage_path("geo/$fileName");
        $remotePath = "http://download.geonames.org/export/dump/$fileName";
        if (! file_exists($localPath)) {
            if (! copy($remotePath, $localPath)) {
                throw new \Exception("Failed to download the file $remotePath");
            }
        }
    }

    private function determinePplParentId($country)
    {
        $geos = DB::table('geo')
            ->select(['id', 'name', 'country', 'a1code', 'level'])
            ->where('country', $country)
            ->whereRaw('parent_id IS NULL')
            ->get();

        $map = $this->mapAdmin1Codes();

        foreach ($geos as $geo) {
            $key = "{$geo->country}.{$geo->a1code}";
            $parentId = Arr::get($map, $key);

            if (! $parentId) {
                $this->warn("Not found parent for: {$geo->id} ({$geo->name})");
                continue;
            }

            DB::table('geo')->where('id', $geo->id)->update(['parent_id' => $parentId]);
        }
    }

    private function buildPplHierarchy($country)
    {
        $hierarchyPplFilePath = storage_path("geo/hierarchy-ppl-$country.txt");
        $countryFilePath = storage_path("geo/$country.txt");

        $map = $this->mapAdmin1Codes();

        $rows = '';
        foreach (file($countryFilePath) as $line) {
            $cols = explode("\t", trim($line));

            if (strpos($cols[7], 'PPL') === false) {
                continue;
            }

            $geoId = $cols[0];
            $key = "{$cols[8]}.{$cols[10]}";
            $geoParentId = Arr::get($map, $key);

            if ($geoParentId) {
                $rows .= "$geoParentId\t$geoId" . PHP_EOL;
            }
        }

        file_put_contents($hierarchyPplFilePath, $rows);
    }

    private function mergeHierarchies($country)
    {
        $files = [
            storage_path('geo/hierarchy-origin.txt'),
            storage_path("geo/hierarchy-ppl-$country.txt"),
        ];

        $lines = [];
        foreach ($files as $file) {
            foreach (file($file) as $line) {
                $lines[] = trim($line);
            }
        }
        $lines = array_unique($lines);

        $content = implode(PHP_EOL, $lines);

        file_put_contents(storage_path("geo/hierarchy-$country.txt"), $content);
    }

    private function mergeAllHierarchies($countries)
    {
        $files = [
            storage_path('geo/hierarchy-origin.txt')
        ];

        foreach ($countries as $country) {
            $files[] = storage_path("geo/hierarchy-ppl-$country.txt");
        }

        $lines = [];
        foreach ($files as $file) {
            foreach (file($file) as $line) {
                $lines[] = trim($line);
            }
        }
        $lines = array_unique($lines);

        $content = implode(PHP_EOL, $lines);

        file_put_contents(storage_path('geo/hierarchy.txt'), $content);
    }

    private function mapAdmin1Codes()
    {
        $map = []; // @example: UA.01 => $parent_id

        $fileName = 'admin1CodesASCII.txt';
        $localPath = storage_path("geo/$fileName");

        foreach (file($localPath) as $line) {
            $cols = explode("\t", trim($line));
            $key = $cols[0];
            $parentId = $cols[3];
            $map[$key] = $parentId;
        }

        return $map;
    }
}
