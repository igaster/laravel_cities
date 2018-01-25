<?php namespace Igaster\LaravelCities;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Route;
use Igaster\LaravelCities\dbTree\EloquentTreeItem;

class Geo extends EloquentTreeItem
{
	protected $table = 'geo';
	protected $guarded = [];
	public $timestamps = false;

    const LEVEL_COUNTRY = 'PCLI';
    const LEVEL_CAPITAL = 'PPLC';
    const LEVEL_1 = 'ADM1';
    const LEVEL_2 = 'ADM2';
    const LEVEL_3 = 'ADM3';


    protected $casts = ['alternames' => 'array'];

    // Hide From JSON
    protected $hidden = ['alternames', 'left', 'right', 'depth'];


    // ----------------------------------------------
    //  Scopes
    // ----------------------------------------------

    public function scopeCountry($query, $countryCode)
    {
        return $query->where('country', $countryCode);
    }

    public function scopeCapital($query)
    {
        return $query->where('level',Geo::LEVEL_CAPITAL);
    }

    public function scopeLevel($query,$level)
    {
        return $query->where('level', $level);
    }

    public function scopeDescendants($query)
    {
        return $query->where('left', '>', $this->left)->where('right', '<', $this->right);
    }

    public function scopeAncenstors($query)
    {
        return $query->where('left', '<', $this->left)->where('right', '>', $this->right);
    }

    public function scopeChildren($query)
    {
        return $query->where(function($query) {
            $query->where('left', '>', $this->left)
                ->where('right', '<', $this->right)
                ->where('depth', $this->depth + 1);
        });        
    }

    public function scopeSearch($query,$search)
    {
        $search = '%'.mb_strtolower($search).'%';

        return $query->where(function($query) use ($search) {
            $query->whereRaw('LOWER(alternames) LIKE ?', [$search])
                ->orWhereRaw('LOWER(name) LIKE ?', [$search]);
        });

    }

    public function scopeAreDescentants($query, Geo $parent)
    {
        return $query->where(function($query) use ($parent) {
            $query->where('left', '>', $parent->left)
                ->where('right', '<', $parent->right);
        });
    }

    public function scopeTest($query)
    {
        return $query;
    }

    // ----------------------------------------------
    //  Mutators
    // ----------------------------------------------

    // public function setXxxAttribute($value){
    //     $this->attributes['xxx'] = $value;     
    // }

    // ----------------------------------------------
    //  Relations
    // ----------------------------------------------


    // ----------------------------------------------
    //  Methods
    // ----------------------------------------------

    // search in `name` and `alternames` / return collection
    public static function searchNames($name, Geo $parent = null)
    {
        $query = self::search($name)->orderBy('name', 'ASC');

        if ($parent) {
            $query->areDescentants($parent);
        }

        return $query->get();
    }

    // get all Countries
    public static function getCountries()
    {
        return self::level(Geo::LEVEL_COUNTRY)->orderBy('name')->get();
    }

    // get Country by country Code (eg US,GR)
    public static function getCountry($countryCode)
    {
        return self::level(Geo::LEVEL_COUNTRY)->country($countryCode)->first();
    }

    // get multiple item by Ids
    public static function getByIds(array $Ids = [])
    {
        return self::whereIn('id',$Ids)->orderBy('name')->get();
    }

    // is imediate Child of $item ?
    public function isChildOf(Geo $item)
    {
        return ($this->left > $item->left) && ($this->right < $item->right) && ($this->depth == $item->depth+1);
    }
    
    // is imediate Parent of $item ?
    public function isParentOf(Geo $item)
    {
        return ($this->left < $item->left) && ($this->right > $item->right) && ($this->depth == $item->depth-1);
    }

    // is Child of $item (any depth) ?
    public function isDescendantOf(Geo $item)
    {
        return ($this->left > $item->left) && ($this->right < $item->right);
    }

    // is Parent of $item (any depth) ?
    public function isAncenstorOf(Geo $item)
    {
        return ($this->left < $item->left) && ($this->right > $item->right);
    }

    // retrieve by name  
    public static function findName($name)
    {
        return self::where('name',$name)->first();
    }

    // get all imediate Children (Collection)
    public function getChildren()
    {
        return self::descendants()->where('depth', $this->depth+1)->orderBy('name')->get();
    }

    // get Parent (Geo)
    public function getParent()
    {
        return self::ancenstors()->where('depth', $this->depth-1)->first();
    }

    // get all Ancnstors (Collection) ordered by level (Country -> City)
    public function getAncensors()
    {
        return self::ancenstors()->orderBy('depth')->get();
    }

    // get all Descendants (Collection) Alphabetical
    public function getDescendants()
    {
        return self::descendants()->orderBy('level')->orderBy('name')->get();
    }



    // Return only $fields as Json. null = Show all 
    public function fliterFields($fields = null){

        if (is_string($fields)){ // Comma Seperated List (eg Url Param)
            $fields = explode(',', $fields);
        }

        if(empty($fields)){
            $this->hidden = [];
        } else {
            $this->hidden = ['id','parent_id','left','right','depth','name','alternames','country','level','population','lat','long'];
            foreach ($fields as $field) {
                $index = array_search($field, $this->hidden);
                if($index !== false){
                    unset($this->hidden[$index]);
                }
            };
            $this->hidden = array_values($this->hidden);
        }

        return $this;
    }

    // ----------------------------------------------
    //  Routes
    // ----------------------------------------------

    public static function ApiRoutes()
    {
        Route::group(['prefix' => 'geo'], function() {
            Route::get('search/{name}/{parent_id?}',    '\Igaster\LaravelCities\GeoController@search');
            Route::get('item/{id}',         '\Igaster\LaravelCities\GeoController@item');
            Route::get('items/{ids}',       '\Igaster\LaravelCities\GeoController@items');
            Route::get('children/{id}',     '\Igaster\LaravelCities\GeoController@children');
            Route::get('parent/{id}',       '\Igaster\LaravelCities\GeoController@parent');
            Route::get('country/{code}',    '\Igaster\LaravelCities\GeoController@country');
            Route::get('countries',         '\Igaster\LaravelCities\GeoController@countries');
            Route::get('ancestors/{id}',    '\Igaster\LaravelCities\GeoController@ancestors');
            Route::get('breadcrumbs/{id}',  '\Igaster\LaravelCities\GeoController@breadcrumbs');
        });
    }

}