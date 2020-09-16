<?php

namespace Igaster\LaravelCities;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Igaster\LaravelCities\dbTree\EloquentTreeItem;

class Geoalternate extends Eloquent
{
    protected $table = 'geoalternate';
    
    protected $guarded = [];

    public $timestamps = false;

    public function getGeoAttribute()
    {
        return $this->geo()->first();
    }

    public function geo()
    {
        return $this->belongsTo(Geo::class);
    }

    public function scopeGeonameid($query, $geonameid)
    {
        return $query->where('geonameid', $geonameid);
    }

    public function scopeIsolanguage($query, $lang)
    {
        if (is_array($lang)) {
            return $query->whereIn('isolanguage', $lang);
        }
        return $query->where('isolanguage', $lang);
    }

    public function scopeIsPreferredName($query, $v)
    {
        return $query->where('isPreferredName', $v);
    }

    public function scopeIsColloquial($query, $v)
    {
        return $query->where('isColloquial', $v);
    }

}
