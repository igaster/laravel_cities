[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg)](https://tldrlegal.com/license/mit-license)

# Introduction

What you get:
- Deploy and use geonames.org (ex MaxCDN) database localy to query countries/citiew 
- Get information like lattitude/longtityde, population etc 
- Optimized [DB tree structure](https://en.wikipedia.org/wiki/Nested_set_model) for searching and traversing the tree.
- Provides an Eloquent model (geo) with multiple query-scopes to help you build your queries.
- Exposes a simple API that you can use to create AJAX calls. (Eg search while typing etc).

What you dont get:
- geoIP & Postalcodes (not included in free sets)
- Map elements smaller than "3rd Administration Division" (=Cities)

# Instructions
	
1. Download & unzip "hieararcy.txt" & "allCountries.txt" from geonames.org (http://download.geonames.org/export/dump)
2. Save to your app's storage folder ('\storage')
3. Add Service Provider in app.php:

    'providers' => [
    	//...
        igaster\laravel_cities\geoServiceProvider::class,
	];

4. Run 

	artisan migrate
	artisan geo:load

Note: If you don't want all the countries, you can download only country specific files (eg US.txt) and import each one of them with:

	artisan geo:load US --append

# Usage

## Searching:
```php
use igaster\laravel_cities\geo;

geo::getCountry('GR');				// Get item by Country code
geo::findName('Nomos Kerkyras');	// Find item by (ascii) name
geo::searchAllNames('Κέρκυρα');		// Find item LIKE Name or any Alternative name
geo::searchAllNames('Samou', geo::getCountry('GR'));	// ... and belongs to an item
```

## Traverse tree
```php
$children 	= $geo->getChildren();			// Get direct Children of $geo (Collection)
$parent 	= $geo->getParent();			// Get single Parent of $geo (Geo)
$ancenstors = $geo->getAncensors();			// Get Ancenstors tree of $geo from top->bottom (Collection)
$descendants= $geo->getDescendants();		// Get all Descentants of $geo alphabetic (Collection)
```


## Check Hierarchy Relations:
```php
$geo1->isParentOf($geo2);		// (Bool) Check if $geo2 is direct Parent of $geo1
$geo2->isChildOf($geo1);		// (Bool) Check if $geo2 is direct Child of $geo1
$geo1->isAncenstorOf($geo2);	// (Bool) Check if $geo2 is Ancenstor of $geo1
$geo2->isDescendantOf($geo1);	// (Bool) Check if $geo2 is Descentant of $geo1
```

## Query scopes (Use them to Build custom queries)
```php
geo::level($level);		// Filter Administration level: geo::LEVEL_COUNTRY, geo::LEVEL_CAPITAL, geo::LEVEL_1, geo::LEVEL_2, geo::LEVEL_3
geo::country('US');		// (Shortcut) Items that belongs to country US 
geo::capital();			// (Shortcut) Items that are capitals
geo::searchAllNames($name); 	// Items that conain $name in name OR alternames (Case InSensitive)
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

# HTTP API

This package defines some API routes that can be used to query the DB through simple HTTP requests:

| URL Endpoind (GET)                | Returns                                                    | Type     |
|-----------------------------------|------------------------------------------------------------|----------|
|api/geo/search/{name}/{parent-id?} | Search of items containing name, (and belong to parent-id) | array    |
|api/geo/item/{id}                  | Get item by id                                             | geo item |
|api/geo/children/{id}              | Get children of item                                       | array    |
|api/geo/parent/{id}                | Get parent of item                                         | geo item |
|api/geo/country/{code}             | get country by two-letter code                             | geo item |
|api/geo/countries                  | list of countries                                          | array    |


