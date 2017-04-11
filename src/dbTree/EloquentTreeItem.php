<?php namespace Igaster\LaravelCities\dbTree;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTreeItem extends Eloquent {

    // Properties:
    // id, parent_id, depth, left, right

	protected $parent = null;

    public static function rebuildTree(){
    	$items = self::all();
    	dd($items);

    }

}