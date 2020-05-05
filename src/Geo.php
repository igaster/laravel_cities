<?php

namespace Igaster\LaravelCities;

use Igaster\LaravelCities\dbTree\EloquentTreeItem;
use Illuminate\Support\Facades\Request;

class Geo extends EloquentTreeItem
{
    protected $table = 'geo';
    protected $guarded = [];
    public $timestamps = false;

    const LEVEL_COUNTRY = 'PCLI';
    const LEVEL_CAPITAL = 'PPLC';

    const LEVEL_PPL = 'PPL';
    // a populated city, town, village, or other agglomeration of buildings where people live and work

    const LEVEL_1 = 'ADM1';
    const LEVEL_2 = 'ADM2';
    const LEVEL_3 = 'ADM3';

    protected $casts = ['alternames' => 'array'];

    // Hide From JSON
    protected $hidden = ['alternames', 'left', 'right', 'depth'];

    protected $alternate = null;

    static public $geoalternateOptions = null;

    // ----------------------------------------------
    //  Scopes
    // ----------------------------------------------

    public function scopeCountry($query, $countryCode)
    {
        return $query->where('country', $countryCode)->alternateNames();
    }

    public function scopeCapital($query)
    {
        return $query->where('level', self::LEVEL_CAPITAL)->alternateNames();
    }

    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level)->alternateNames();
    }

    public function scopeDescendants($query)
    {
        return $query->where('left', '>', $this->left)->where('right', '<', $this->right)->alternateNames();
    }

    public function scopeAncestors($query)
    {
        return $query->where('left', '<', $this->left)->where('right', '>', $this->right)->alternateNames();
    }
    
    /**
     * old method with a typo, kept for backwards compatibility.
     * @deprecated
     */
    public function scopeAncenstors($query)
    {
        return $this->scopeAncestors($query);
    }

    public function scopeChildren($query)
    {
        return $query->where(function ($query) {
            $query->where('left', '>', $this->left)
                ->where('right', '<', $this->right)
                ->where('depth', $this->depth + 1);
        })->alternateNames();
    }

    public function scopeSearch($query, $search)
    {
        $search = '%' . mb_strtolower($search) . '%';

        return $query->where(function ($query) use ($search) {
            $query->whereRaw('LOWER(alternames) LIKE ?', [$search])
                ->orWhereRaw('LOWER(name) LIKE ?', [$search]);
        });
    }

    public function scopeAreDescendants($query, Geo $parent)
    {
        return $query->where(function ($query) use ($parent) {
            $query->where('left', '>', $parent->left)
                ->where('right', '<', $parent->right);
        });
    }

    public function scopeAreDescentants($query, Geo $parent)
    {
        return $this->scopeAreDescendants($query, $parent);
    }

    public static function scopeAlternatenames($query) {
        if (static::$geoalternateOptions->alternateNames) {
            return $query->with([
                'geoalternate' => function ($query) {
                    $query = Geo::filterAlternate($query, Geo::$geoalternateOptions);
                }
            ]);
        }
        return $query;
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
            $query->areDescendants($parent);
        }

        return $query->get();
    }

    // get all Countries
    public static function getCountries()
    {
        return self::level(self::LEVEL_COUNTRY)->orderBy('name')->get();
    }

    // get Country by country Code (eg US,GR)
    public static function getCountry($countryCode)
    {
        return self::level(self::LEVEL_COUNTRY)->country($countryCode)->first();
    }

    // get multiple item by Ids
    public static function getByIds(array $Ids = [])
    {
        return self::whereIn('id', $Ids)->orderBy('name')->get();
    }

    // is imediate Child of $item ?
    public function isChildOf(Geo $item)
    {
        return ($this->left > $item->left) && ($this->right < $item->right) && ($this->depth == $item->depth + 1);
    }
    
    // is imediate Parent of $item ?
    public function isParentOf(Geo $item)
    {
        return ($this->left < $item->left) && ($this->right > $item->right) && ($this->depth == $item->depth - 1);
    }

    // is Child of $item (any depth) ?
    public function isDescendantOf(Geo $item)
    {
        return ($this->left > $item->left) && ($this->right < $item->right);
    }

    // is Parent of $item (any depth) ?
    public function isAncestorOf(Geo $item)
    {
        return ($this->left < $item->left) && ($this->right > $item->right);
    }

    /**
     * old method with a typo, kept for backwards compatibility.
     * @deprecated 
     */
    public function isAncenstorOf(Geo $item)
    {
        return $this->isAncestorOf($item);
    }

    // retrieve by name
    public static function findName($name)
    {
        return self::where('name', $name)->first();
    }

    // get all imediate Children (Collection)
    public function getChildren()
    {
        return self::descendants()->where('depth', $this->depth + 1)->orderBy('name')->get();
    }

    // get Parent (Geo)
    public function getParent()
    {
        return self::find($this->parent_id);
    }

    // get all Ancnstors (Collection) ordered by level (Country -> City)
    public function getAncestors()
    {
        return self::ancestors()->orderBy('depth')->get();
    }

    /**
     * old method with a typo, kept for backwards compatibility.
     * @deprecated
     */
    public function getAncensors()
    {
        return $this->getAncestors();
    }

    // get all Descendants (Collection) Alphabetical
    public function getDescendants()
    {
        return self::descendants()->orderBy('level')->orderBy('name')->get();
    }

    // Return only $fields as Json. null = Show all
    public function filterFields($fields = null)
    {
        if (is_string($fields)) { // Comma Seperated List (eg Url Param)
            $fields = explode(',', $fields);
        }

        if (empty($fields)) {
            $this->hidden = [];
        } else {
            $this->hidden = ['id', 'parent_id', 'left', 'right', 'depth', 'name', 'alternames', 'country', 'level', 'population', 'lat', 'long'];
            foreach ($fields as $field) {
                $index = array_search($field, $this->hidden);
                if ($index !== false) {
                    unset($this->hidden[$index]);
                }
            };
            $this->hidden = array_values($this->hidden);
        }

        return $this;
    }

    /**
     * old method with a typo, kept for backwards compatibility.
     * @deprecated version
     */
    public function fliterFields($fields = null) 
    {
        return $this->filterFields($fields);
    }

    static public function filterAlternate($query, GeoalternateOptions $options) {
        if (!$options->alternateNames) {
            return $query;
        }
        if ($options->isolanguage) {
            $query = $query->isoLanguage($options->isolanguage);
        }
        if ($options->isPreferredName) {
            $query = $query->isPreferredName(1);
        }
        if ($options->isShortName) {
            $query = $query->isShortName(1);
        }
        return $query;
    }

    public function getGeoalternateAttribute()
    {
        return static::filterAlternate($this->geoalternate(), static::$geoalternateOptions)->get();
    }

    public function geoalternate()
    {
        return $this->hasMany(Geoalternate::class, 'geonameid');
    }

    // ----------------------------------------------
    //  Routes
    // ----------------------------------------------

    public static function ApiRoutes()
    {
        require_once __DIR__ . '/routes.php';
    }
}

Geo::$geoalternateOptions = new GeoalternateOptions();