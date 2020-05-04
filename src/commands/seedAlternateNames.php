<?php

namespace Igaster\LaravelCities\commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;

class seedAlternateNames extends Command
{
    protected $signature = 'geo:alternate {--chunk=1000} {--append}}';
    protected $description = 'Load + Parse + Save to DB a geodata alternate names file.';

    private $pdo;
    private $driver;

    private $alternateItems;

    private $batch = 0;

    private $chunkSize = 1000;

    public function __construct()
    {
        parent::__construct();
        
        $connection = config('database.default');
        $this->driver = strtolower(config("database.connections.{$connection}.driver"));

        $this->alternateItems = [];
    }

    public function sql($sql)
    {
        $result = $this->pdo->query($sql);
        if ($result === false) {
            throw new Exception("Error in SQL : '$sql'\n" . PDO::errorInfo(), 1);
        }

        return $result->fetch();
    }

    /**
     * Get fully qualified table name with prefix if any
     *
     * @return string
     */
    public function getFullyQualifiedTableName() : string
    {
        return DB::getTablePrefix() . 'geoalternate';
    }

    protected function getColumnsAsStringDelimated($delimeter = '"', bool $onlyPrefix = false)
    {
        $columns = [
            'alternateNameId',
            'geonameid',
            'isolanguage',
            'alternatename',
            'isPreferredName',
            'isShortName', 
            'isColloquial',
            'isHistoric',
            'from',
            'to'
        ];	

        $modifiedColumns = [];

        foreach ($columns as $column) {
            $modifiedColumns[] = $delimeter . $column . (($onlyPrefix) ? '' : $delimeter);
        }
        
        return implode(',', $modifiedColumns);
    }

    public function getDBStatement() : array
    {
        $sql = "INSERT INTO {$this->getFullyQualifiedTableName()} ( {$this->getColumnsAsStringDelimated()} ) VALUES ( {$this->getColumnsAsStringDelimated(':', true)} )";
        
        if ($this->driver == 'mysql') {
            $sql = "INSERT INTO {$this->getFullyQualifiedTableName()} ( {$this->getColumnsAsStringDelimated('`')} ) VALUES ( {$this->getColumnsAsStringDelimated(':', true)} )";
        }

        return [$this->pdo->prepare($sql), $sql];
    }

    public function readFile(string $fileName)
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
            $data = explode("\t", $line);

            // Check for errors
            if (count($data) !== 10) {
                dd($data[0], $data[2]);
            }

            $this->alternateItems[] = $data;
            $count++;

            $progress = ftell($handle) / $filesize * 100;
            $progressBar->setProgress($progress);

            if (count($this->alternateItems) >= $this->chunkSize) {
                $this->processItems();
            }
        }

        $progressBar->finish();

        $this->info(" Finished Reading File. $count items loaded</info>");
    }

    public function processItems()
    {
        // write to persistent storage
        $this->writeToDb();

        // reset the chunk
        $this->alternateItems = [];

        $this->info(PHP_EOL . 'Processed Batch ' . $this->batch);
        $this->batch++;
    }

    public function handle()
    {
        $this->pdo = DB::connection()->getPdo(PDO::FETCH_ASSOC);

        if (! Schema::hasTable('geoalternate')) {
            return;
        }
        
        $start = microtime(true);
        $fileName = storage_path("geo/alternateNamesV2.txt");
        $isAppend = $this->option('append');

        $this->chunkSize = $this->option('chunk');

        $this->info("Start seeding for alternateNamesV2");

        DB::beginTransaction();

        // Clear Table
        if (!$isAppend) {
            $this->info("Truncating '{$this->getFullyQualifiedTableName()}' table...");
            DB::table('geo')->truncate();
        }

        // Read Raw file
        $this->readFile($fileName);

        // Store Tree in DB
        $this->writeToDb();
        
        //Lets get back FOREIGN_KEY_CHECKS to laravel
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info(PHP_EOL . ' Relation checks enabled');

        DB::commit();

        $this->info(' Done</info>');
        $time_elapsed_secs = microtime(true) - $start;
        $this->info("Timing: $time_elapsed_secs sec</info>");
    }

    public function writeToDb()
    {
        // Store Tree in DB
        $this->info('Writing in Database</info>');
        
        [$stmt, $sql] = $this->getDBStatement();

        $count = 0;
        $totalCount = count($this->alternateItems);

        $progressBar = new ProgressBar($this->output, 100);

        foreach ($this->alternateItems as $item) {
            $params = [
                'alternateNameId' => $item[0],
                'geonameid' => $item[1],
                'isolanguage' => $item[2],
                'alternatename' => $item[3],
                'isPreferredName' => (int)$item[4],
                'isShortName' => (int)$item[5],
                'isColloquial' => (int)$item[6],
                'isHistoric' => (int)$item[7],
                'from' => $item[8] ? $item[8] : null,
                'to' => $item[9] ? $item[9] : null
            ];

            if ($stmt->execute($params) === false) {
                $error = "Error in SQL : '$sql'\n" . PDO::errorInfo() . "\nParams: \n$params";
                throw new Exception($error, 1);
            }

            $progress = $count++ / $totalCount * 100;
            $progressBar->setProgress($progress);
        }

        $progressBar->finish();
    }
}
