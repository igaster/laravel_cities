<?php

namespace Igaster\LaravelCities\Tests\App;

use Illuminate\Database\Eloquent\Model as Eloquent;

class TestModel extends Eloquent
{
    protected $table = 'test_table';
	protected $guarded = [];
	public $timestamps = false;
}