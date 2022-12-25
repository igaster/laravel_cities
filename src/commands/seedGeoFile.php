<?php

namespace Igaster\LaravelCities\commands;

use Exception;
use Igaster\LaravelCities\commands\helpers\geoCollection;
use Igaster\LaravelCities\commands\helpers\geoItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;

class seedGeoFile extends Command
{
    protected $signature = 'geo:seed {country?} {--append} {--chunk=1000} {--insertSize=1000}';
    protected $description = 'Load + Parse + Save to DB a geodata file.';

    private $pdo;
    private $driver;

    private $geoItems;

    private $batch = 0;

    private $chunkSize = 1000;

    private $insertSize = 1000;

    private $columns = [
        'id', 'parent_id', 'left', 'right', 'depth', 'name', 'alternames', 'country', 'a1code', 'level', 'population', 'lat', 'long', 'timezone',
    ];

    public function __construct()
    {
        parent::__construct();
        
        $connection = config('database.default');
        $this->driver = strtolower(config("database.connections.{$connection}.driver"));

        $this->geoItems = new geoCollection();
    }

    public function sql($sql)
    {
        $result = $this->pdo->query($sql);
        if ($result === false) {
            throw new Exception("Error in SQL : '$sql'\n" . PDO::errorInfo(), 1);
        }

        return $result->fetch();
    }

    public function buildDbTree($item, $count = 1, $depth = 0)
    {
        $item->left = $count++;
        $item->depth = $depth;
        foreach ($item->getChildren() as $child) {
            $count = $this->buildDbTree($child, $count, $depth + 1);
        }
        $item->right = $count++;

        return $count;
    }

    public function printTree($item)
    {
        $levelStr = str_repeat('--', $item->depth);
        $this->info(sprintf('%s %s [%d,%d]', $levelStr, $item->getName(), $item->left, $item->right));
        foreach ($item->getChildren() as $child) {
            $this->printTree($child);
        }
    }

    /**
     * Get fully qualified table name with prefix if any
     *
     * @return string
     */
    public function getFullyQualifiedTableName() : string
    {
        return DB::getTablePrefix() . 'geo';
    }

    protected function getColumnsAsStringDelimated($delimeter = '"', bool $onlyPrefix = false)
    {
        $modifiedColumns = [];

        foreach ($this->columns as $column) {
            $modifiedColumns[] = $delimeter . $column . (($onlyPrefix) ? '' : $delimeter);
        }
        
        return implode(',', $modifiedColumns);
    }

    public function getDBStatement($totalItems) : array
    {
        $strItem = "(" . implode(',', array_fill(0, count($this->columns), '?')) . ")";
        $totalItems = implode(',', array_fill(0, $totalItems, $strItem));
       
        if ($this->driver == 'mysql') {
            $sql = "INSERT INTO {$this->getFullyQualifiedTableName()} ( {$this->getColumnsAsStringDelimated('`')} ) VALUES " .
                $totalItems;
        }
        else {
            $sql = "INSERT INTO {$this->getFullyQualifiedTableName()} ( {$this->getColumnsAsStringDelimated()} ) VALUES " .
                $totalItems;
        }

        return [$this->pdo->prepare($sql), $sql];
    }

    public function readFile(string $fileName, string $country = null)
    {
        $this->info("Reading File '$fileName'");
        $filesize = filesize($fileName);
        $handle = fopen($fileName, 'r');
        $count = 0;

        $progressBar = new ProgressBar($this->output, 100);

        while (($line = fgets($handle)) !== false) {
            // ignore empty lines and comments
            if (! $line || $line === '' || strpos($line, '#') === 0) {
                continue;
            }

            // Convert TAB sepereted line to array
            $line = explode("\t", $line);

            // Check for errors
            if (count($line) !== 19) {
                dd($line[0], $line[2]);
            }

            switch ($line[7]) {
                case 'PCLI':    // Country
                case 'PPLC':    // Capital
                case 'ADM1':
                case 'ADM2':
                case 'ADM3':   // 8 sec
                case 'PPLA':   // областные центры
                case 'PPLA2':  // Корсунь
                //case 'PPL':    // a city, town, village, or other agglomeration of buildings where people live and work
                    // 185 sec
                    $this->geoItems->add(new geoItem($line, $this->geoItems));
                    $count++;
                    break;
            }
            $progress = ftell($handle) / $filesize * 100;
            $progressBar->setProgress($progress);

            if (count($this->geoItems->items) >= $this->chunkSize) {
                $this->processItems($country);
            }
        }
        
        $this->processItems($country);

        $progressBar->finish();

        $this->info(" Finished Reading File. $count items loaded</info>");
    }

    public function processItems($country)
    {
        // Read hierarchy
        $this->readHierarcy($country);

        // Build Tree
        $this->buildTree();

        // write to persistent storage
        $this->writeToDb();

        //reset the chunk
        $this->geoItems->reset();

        $this->info(PHP_EOL . 'Processed Batch ' . $this->batch);
        $this->batch++;
    }

    public function handle()
    {
        $this->pdo = DB::connection()->getPdo(PDO::FETCH_ASSOC);

        if (! Schema::hasTable('geo')) {
            return;
        }
        
        $start = microtime(true);
        $country = strtoupper($this->argument('country'));
        $sourceName = $country ? $country : 'allCountries';
        $fileName = storage_path("geo/{$sourceName}.txt");
        $isAppend = $this->option('append');

        $this->chunkSize = $this->option('chunk');

        $this->info("Start seeding for $sourceName");

        DB::beginTransaction();

        // Clear Table
        if (! $isAppend) {
            $this->info("Truncating '{$this->getFullyQualifiedTableName()}' table...");
            DB::table('geo')->truncate();
        }

        // Read Raw file
        $this->readFile($fileName, $country);

        // Read hierarchy
        //$this->readHierarcy($country);

        // Build Tree
        //$this->buildTree();

        // Store Tree in DB
        //$this->writeToDb();
        
        //Lets get back MySQL FOREIGN_KEY_CHECKS to laravel
        if(DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info(PHP_EOL . ' Relation checks enabled');

        DB::commit();

        $this->info(' Done</info>');
        $time_elapsed_secs = microtime(true) - $start;
        $this->info("Timing: $time_elapsed_secs sec</info>");
    }

    public function readHierarcy(string $country)
    {
        //if all countries
        $fileName = storage_path('geo/hierarchy.txt');
        //ini_set('xdebug.max_nesting_level', 5000);
        //commented as hierarchy file is same for all as of now as per geo db
//         if ($country != '') {
//             $fileName = storage_path("geo/hierarchy-$country.txt");
//         }
        
        $this->info("Opening File '$fileName'</info>");
        $handle = fopen($fileName, 'r');
        $filesize = filesize($fileName);
        $count = 0;
        $progressBar = new ProgressBar($this->output, 100);
        while (($line = fgetcsv($handle, 0, "\t")) !== false) {
            $parent = $this->geoItems->findGeoId($line[0]);
            $child = $this->geoItems->findGeoId($line[1]);

            if ($parent !== null && $child !== null) {
                $parent->addChild($line[1]);
                $child->setParent($line[0]);
                $count++;
            }

            $progress = ftell($handle) / $filesize * 100;
            $progressBar->setProgress($progress);
        }
        $this->info(" Hierarcy building completed. $count items loaded</info>");
    }

    public function buildTree()
    {
        $count = 0;
        $countOrphan = 0;
        $sql = 'SELECT MAX("right") as maxRight FROM ' . $this->getFullyQualifiedTableName();
        $result = $this->sql($sql);
        $maxBoundary = (isset($result['maxRight']) && is_numeric($result['maxRight'])) ? $result['maxRight'] + 1 : 0;

        foreach ($this->geoItems->items as $item) {
            if ($item->parentId === null) {
                if ($item->data[7] !== 'PCLI') {
                    // $this->info("- Skiping Orphan {$item->data[2]} #{$item->data[0]}");
                    $countOrphan++;
                    continue;
                }

                $count++;
                $this->info(PHP_EOL . "+ Building Tree for Country: {$item->data[2]} #{$item->data[0]}");

                $maxBoundary = $this->buildDbTree($item, $maxBoundary, 0);
                // $this->printTree($item,$output);
            }
        }

        $this->info(PHP_EOL . "Finished: {$count} Countries imported.  $countOrphan orphan items skiped</info>");
    }

    public function writeToDb()
    {
        // Store Tree in DB
        // $this->info('Writing in Database');
        
        [$stmt, $sql] = $this->getDBStatement($this->insertSize);

        $count = 0;

        $totalCount = count($this->geoItems->items);

        // $progressBar = new ProgressBar($this->output, 100);

        $batch = [];

        $totalToCommit = $this->insertSize * count($this->columns);
        
        foreach ($this->geoItems->items as $item) {
            // $params = [
            //     ':id' => $item->getId(),
            //     ':parent_id' => $item->parentId,
            //     ':left' => $item->left,
            //     ':right' => $item->right,
            //     ':depth' => $item->depth,
            //     ':name' => substr($item->data[2], 0, 40),
            //     ':alternames' => $item->data[3],
            //     ':country' => $item->data[8],
            //     ':a1code' => $item->data[10],
            //     ':level' => $item->data[7],
            //     ':population' => $item->data[14],
            //     ':lat' => $item->data[4],
            //     ':long' => $item->data[5],
            //     ':timezone' => $item->data[17],
            // ];

            array_push($batch,
                $item->getId(),
                $item->parentId,
                $item->left,
                $item->right,
                $item->depth,
                substr($item->data[2], 0, 40),
                $item->data[3],
                $item->data[8],
                $item->data[10],
                $item->data[7],
                $item->data[14],
                $item->data[4],
                $item->data[5],
                $item->data[17],
            );

            if (count($batch) >= $totalToCommit) {
                if ($stmt->execute($batch) === false) {
                    $error = "Error in SQL : '$sql'\n" . print_r($stmt->errorInfo(), true) . "\n";
                    throw new Exception($error, 1);
                }
                $batch = [];
            }

            $progress = $count++ / $totalCount * 100;
            // $progressBar->setProgress($progress);
        }

        if (count($batch)) {
            [$stmt, $sql] = $this->getDBStatement(count($batch)/count($this->columns));
            if ($stmt->execute($batch) === false) {
                $error = "Error in SQL : '$sql'\n" . print_r($stmt->errorInfo(), true) . "\n";
                throw new Exception($error, 1);
            }
        }
        // $progressBar->finish();
    }

}
