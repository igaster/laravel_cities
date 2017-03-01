<?php namespace Igaster\LaravelCities;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Route;

class Geo extends Eloquent {
	protected $table = 'geo';
	protected $guarded = [];
	public $timestamps = false;

    const LEVEL_COUNTRY = 'PCLI';
    const LEVEL_CAPITAL = 'PPLC';
    const LEVEL_1 = 'ADM1';
    const LEVEL_2 = 'ADM2';
    const LEVEL_3 = 'ADM3';


    protected   $casts = [
        'alternames' => 'array',
    ];

    // ----------------------------------------------
    //  Scopes
    // ----------------------------------------------

    public function scopeCountry($query, $countryCode){
        return $query->where('country', $countryCode);
    }

    public function scopeCapital($query){
        return $query->where('level',Geo::LEVEL_CAPITAL);
    }

    public function scopeLevel($query,$level){
        return $query->where('level',$level);
    }

    public function scopeDescendants($query){
        return $query->where('left', '>', $this->left)->where('right', '<', $this->right);
    }

    public function scopeAncenstors($query){
        return $query->where('left','<', $this->left)->where('right', '>', $this->right);
    }

    public function scopeChildren($query){
        return $query->where(function($query) 
        {
            $query->where('left', '>', $this->left)
                ->where('right', '<', $this->right)
                ->where('depth', $this->depth+1);
        });        
    }

    public function scopeSearchAllNames($query,$search){
        $search = '%'.mb_strtolower($search).'%';

        return $query->where(function($query) use($search)
        {
            $query->whereRaw('LOWER(alternames) LIKE ?', [$search])
                ->orWhereRaw('LOWER(name) LIKE ?', [$search]);
        });

    }

    public function scopeHasAncestor($query,Geo $parent){
        return $query->where(function($query) use($parent)
        {
            $query->where('left', '>', $parent->left)
                ->where('right', '<', $parent->right);
        });
    }

    // ----------------------------------------------
    //  Mutators
    // ----------------------------------------------

    public function setXxxAttribute($value){
        $this->attributes['xxx'] = $value;     
    }

    // ----------------------------------------------
    //  Relations
    // ----------------------------------------------


    // ----------------------------------------------
    //  Methods
    // ----------------------------------------------

    // is imediate Child of $item ?
    public function isChildOf(Geo $item){
        return ($this->left > $item->left) && ($this->right < $item->right) && ($this->depth == $item->depth+1);
    }
    
    // is imediate Parent of $item ?
    public function isParentOf(Geo $item){
        return ($this->left < $item->left) && ($this->right > $item->right) && ($this->depth == $item->depth-1);
    }

    // is Child of $item (any depth) ?
    public function isDescendantOf(Geo $item){
        return ($this->left > $item->left) && ($this->right < $item->right);
    }

    // is Parent of $item (any depth) ?
    public function isAncenstorOf(Geo $item){
        return ($this->left < $item->left) && ($this->right > $item->right);
    }

    // retrieve by name  
    public static function findName($name){
        return self::where('name',$name)->first();
    }

    // search in `name` and `alternames` / return collection
    public static function searchNames($name, Geo $parent =null){
        $query = self::searchAllNames($name)->orderBy('name', 'ASC');

        if ($parent){
            $query->hasAncestor($parent);
        }

        return $query->get();

        // $sql = "SELECT * 
        //     FROM geo 
        //     WHERE (name LIKE :name1
        //         OR JSON_SEARCH(alternames, 'all', :name2) IS NOT NULL) "
        //     .($parent ? "AND `left` > {$parent->left} AND `right` < {$parent->right} " : "")
        //     ."ORDER BY name ASC";
        // $sth = \DB::connection()->getPdo()->prepare($sql);
        // $sth->bindValue(':name1', "%{$name}%");
        // $sth->bindValue(':name2', "%{$name}%");
        // $sth->execute();
        // $data = $sth->fetchAll(\PDO::FETCH_OBJ);
        // return self::hydrate($data);
    }

    // get all imediate Children (Collection)
    public function getChildren(){
        return self::descendants()->where('depth', $this->depth+1)->orderBy('name')->get();
    }

    // get Parent (Geo)
    public function getParent(){
        return self::ancenstors()->where('depth', $this->depth-1)->first();
    }

    // get all Ancnstors (Collection) ordered by level (Country -> City)
    public function getAncensors(){
        return self::ancenstors()->orderBy('depth')->get();
    }

    // get all Descendants (Collection) Alphabetical
   public function getDescendants(){
        return self::descendants()->orderBy('level')->orderBy('name')->get();
    }

    // get all Countries
    public static function getCountries(){
        return self::level(Geo::LEVEL_COUNTRY)->orderBy('name')->get();
    }

    // get Country by country Code (eg US,GR)
    public static function getCountry($countryCode){
        return self::level(Geo::LEVEL_COUNTRY)->country($countryCode)->first();
    }

    // ----------------------------------------------
    //  Routes
    // ----------------------------------------------

    public static function ApiRoutes(){
        Route::group(['prefix' => 'geo'], function(){
            Route::get('search/{name}/{parent_id?}',    '\Igaster\LaravelCities\geoController@search');
            Route::get('item/{id}',         '\Igaster\LaravelCities\geoController@item');
            Route::get('children/{id}',     '\Igaster\LaravelCities\geoController@children');
            Route::get('parent/{id}',       '\Igaster\LaravelCities\geoController@parent');
            Route::get('country/{code}',    '\Igaster\LaravelCities\geoController@country');
            Route::get('countries',         '\Igaster\LaravelCities\geoController@countries');
        });
    }

}