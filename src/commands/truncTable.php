<?php namespace Igaster\LaravelCities\commands;

use Illuminate\Console\Command;

class truncTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo:cleardb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
         *  Some Time You need to have relation to this model
         *  So first Laravel should ignore this.
         */
        \Eloquent::unguard();
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->info('Relation checks disabled');
        \DB::table('geo')->truncate();
        $this->info('Table "geo" is empty now.');
    }
}
