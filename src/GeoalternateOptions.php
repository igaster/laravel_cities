<?php

namespace Igaster\LaravelCities;

use Illuminate\Http\Request;

class GeoalternateOptions
{
    public $alternateNames = false;
    
    public $isolanguage = null;
    
    public $isPreferredName = false;
    
    public $isShortName = false;

    public function __construct(Request $request = null) {
        if (!$request) { // default, nothing
            return;
        }
        if ($request->has('geoalternate')) {
            $this->alternateNames = true;
            if ($request->has('isolanguage')) {
                $this->isolanguage = explode(',', $request->input('isolanguage'));
            }
            if ($request->has('isPreferredName')) {
                $this->isPreferredName = true;
            }
            if ($request->has('isShortName')) {
                $this->isShortName = true;
            }
        }
    }
}
