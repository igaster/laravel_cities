[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg)](https://tldrlegal.com/license/mit-license)
[![Downloads](https://img.shields.io/packagist/dt/igaster/laravel_cities.svg)](https://packagist.org/packages/igaster/laravel_cities)
[![Build Status](https://img.shields.io/travis/igaster/laravel_cities.svg)](https://travis-ci.org/igaster/laravel_cities)
[![Codecov](https://img.shields.io/codecov/c/github/igaster/laravel_cities.svg)](https://codecov.io/github/igaster/laravel_cities)

### Instructions
	
	1. Download hieararcy.txt & allCountries.txt from Geonames (http://download.geonames.org/export/dump)
	2. Save to /storage folder
	3. Add Service Provider in app.php:
	    'providers' => [
	    	//...
	        igaster\laravel_cities\geoServiceProvider::class,
		];

	3. Run 
		artisan migrate
		artisan geo:parse [?countryCode] [--append]

### Usage: Geo Model

# Searching:
```php
use igaster\laravel_cities\geo;

geo::getCountry('GR');				// Get item by Country code
geo::findName('Nomos Kerkyras');	// Find item by (ascii) name
geo::searchAllNames('Κέρκυρα');		// Find item LIKE Name or any Alternative name
geo::searchAllNames('Samou', geo::getCountry('GR'));	// ... and belongs to an item
```

# Check Hierarchy Relations:
```php
$geo1->isParentOf($geo2);		// (Bool) Check if $geo2 is direct Parent of $geo1
$geo2->isChildOf($geo1);		// (Bool) Check if $geo2 is direct Child of $geo1
$geo1->isAncenstorOf($geo2);	// (Bool) Check if $geo2 is Ancenstor of $geo1
$geo2->isDescendantOf($geo1);	// (Bool) Check if $geo2 is Descentant of $geo1
```

# Traverse tree
```php
$children 	= $geo->getChildren();			// Get direct Children of $geo (Collection)
$parent 	= $geo->getParent();			// Get single Parent of $geo (Geo)
$ancenstors = $geo->getAncensors();			// Get Ancenstors tree of $geo from top->bottom (Collection)
$descendants= $geo->getDescendants();		// Get all Descentants of $geo alphabetic (Collection)
```

# Scopes (Build Queries Filters)
```php
geo::level($level);		// Filter Administration level: geo::LEVEL_COUNTRY, geo::LEVEL_CAPITAL, geo::LEVEL_1, geo::LEVEL_2, geo::LEVEL_3
geo::country('US');		// (Shortcut) Items that belongs to country US 
geo::capital();			// (Shortcut) Items that are capitals
geo::searchAllNames($needle); 	// Search $needle in name + alternames (Case InSensitive)
geo::hasParent($geo); 			// Items that are direct children of $geo
$geo->ancenstors();		// Items that contain $geo
$geo->descendants();	// Items that belong to $geo
$geo->children();		// Items that are direct children of $geo


//--Scope usage Examples:

// Get the States of USA in aplhabetic order
geo::getCountry('US')
	->children()
	->orderBy('name')
	->get();

// Get the 3 biggest cities of Greece
geo::getCountry('GR')
	->level(geo::LEVEL_3)
	->orderBy('population','DESC')
	->limit(3)
	->get();
```

### HTTP API

| URL Endpoind (GET)                | Returns                                                  | Type     |
|-----------------------------------|----------------------------------------------------------|----------|
|api/geo/search/{name}/{parent-id?} | List of items containing name, (and belong to parent-id) | array    |
|api/geo/item/{id}                  | Get item by id                                           | item     |
|api/geo/children/{id}              | Get children of item                                     | array    |
|api/geo/parent/{id}                | Get parent of item                                       | item     |
|api/geo/country/{code}             | get country by two-letter code                           | item     |
|api/geo/countries                  | list of countries                                        | array    |


