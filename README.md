[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg)](https://tldrlegal.com/license/mit-license)

# Introduction

What you get:
- Deploy and use geonames.org (ex MaxCDN) database localy to query countries/cities
- Get information like lattitude/longtityde, population etc 
- Optimized [DB tree structure](https://en.wikipedia.org/wiki/Nested_set_model) for searching and traversing the tree.
- Provides an Eloquent model (geo) with multiple query-scopes to help you build your queries.
- Exposes a simple API that you can use to create AJAX calls. (Eg search while typing etc).

What you dont get:
- geoIP & Postalcodes (not included in free sets)
- Map elements smaller than "3rd Administration Division" (=Cities)

# Instructions
	
- Download & unzip "hieararcy.txt" & "allCountries.txt" from geonames.org (http://download.geonames.org/export/dump)
- Save to your app's storage folder ('\storage')


[Tip] Download on your remote server with:
```
wget http://download.geonames.org/export/dump/allCountries.zip && unzip allCountries.zip && rm allCountries.zip
wget http://download.geonames.org/export/dump/hierarchy.zip && unzip hierarchy.zip && rm hierarchy.zip
```

- Install with copmoser. Run:

```
composer require igaster/laravel_cities
```

- Add Service Provider in app.php:

```php
'providers' => [
    //...
    Igaster\LaravelCities\GeoServiceProvider::class,
];
```

- Migrate and Seed. Run:

```
artisan migrate
artisan geo:seed
```

Note: If you don't want all the countries, you can download only country specific files (eg US.txt) and import each one of them with:

```
artisan geo:load US --append
```

# Geo Model:

You can use `Igaster\LaravelCities\Geo` Model to access the database. List of available properties:

```php
$geo->name;       // name of geographical point in plain ascii
$geo->alternames; // Array of alternate names (Stored as Json)
$geo->country;    // 2-letter country code (ISO-3166)
$geo->id;         // Original id from geonames.org database (geonameid)
$geo->population; // Population (Where provided)
$geo->lat;        // latitude in decimal degrees (wgs84)
$geo->long;       // longitude in decimal degrees (wgs84)
$geo->level;      // Administrator level code (feature code)
// parent_id, left, right, depth: Used to build hierarcy tree
```

Visit http://www.geonames.org > Info, for a more detailed description.

# Usage

## Searching:
```php
use Igaster\LaravelCities\Geo;

Geo::getCountries();                // Get a Collection of all countries
Geo::getCountry('US');				// Get item by Country code
Geo::findName('Nomos Kerkyras');	// Find item by (ascii) name
Geo::searchNames('york');			// Search item by all alternative names. Case insensitive 
Geo::searchNames('vegas', Geo::getCountry('US'));	// ... and belongs to an item
Geo::getByIds([390903,3175395]); 	// Get a Collection of items by Ids
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
Geo::level($level);		// Filter by Administration level: 
                        // Geo::LEVEL_COUNTRY, Geo::LEVEL_CAPITAL, Geo::LEVEL_1, Geo::LEVEL_2, Geo::LEVEL_3
Geo::country('US');		// (Shortcut) Items that belongs to country US 
Geo::capital();			// (Shortcut) Items that are capitals
Geo::search($name); 	// Items that conain $name in name OR alternames (Case InSensitive)
Geo::areDescentants($geo); 		// Items that belong to $geo

$geo->ancenstors();		// Items that contain $geo
$geo->descendants();	// Items that belong to $geo
$geo->children();		// Items that are direct children of $geo


//--Scope usage Examples:

// Get the States of USA in aplhabetic order
Geo::getCountry('US')
	->children()
	->orderBy('name')
	->get();

// Get the 3 biggest cities of Greece
Geo::getCountry('GR')
	->level(Geo::LEVEL_3)
	->orderBy('population','DESC')
	->limit(3)
	->get();
```

If you need more functionality you can extend `Igaster\LaravelCities\Geo` model and add your methods.

# HTTP API

This package defines some API routes that can be used to query the DB through simple HTTP requests. To use them insert in your routes file:

```php
\Igaster\LaravelCities\Geo::ApiRoutes();
```

For example if you insert them in your `routes\api.php` (recomended) then the following URLs will be registered:


| URL Endpoind (GET)                | Description                                               | Returns (JSON) |
|-----------------------------------|-----------------------------------------------------------|----------------|
|api/geo/search/{name}/{parent-id?} | Search items containing 'name', (and belong to parent-id) | Collection     |
|api/geo/item/{id}                  | Get item by id                                            | Geo            |
|api/geo/items/{ids}                | Get items by ids (comma seperated list)                   | Collection     |
|api/geo/children/{id}              | Get children of item                                      | Collection     |
|api/geo/parent/{id}                | Get parent of item                                        | Geo            |
|api/geo/country/{code}             | get country by two-letter code                            | Geo            |
|api/geo/countries                  | list of countries                                         | Collection     |

The response is always a JSON representation of either a Geo class or a Collection.

# Vue Component

A [Vue component](https://github.com/igaster/laravel_cities/blob/master/vue/geo-slect.vue) is shipped with this package that plugs into the provided API and provides an interactive way to pick a location through a series of steps. Sorry, no live demo yet, just some screenshots:

Step 1: Select your location. Drop down lists loads asynchronous:

![Select Location](/docs/1.jpg?raw=true)

Step 2: Reached to a destination. Path is displayed and button to edit selection:

![Finished Selection](/docs/2.jpg?raw=true)

Step 3: On form submition several fields are beeing submited:

![Form Submited](/docs/3.jpg?raw=true)

### Usage Guide

Assuming that you are using Webpack to compile your assets, and you have included `vue-app.js`:

### Add in your application

In your main vue-app.js file add the component declaration:

`Vue.component('geo-select', require('RELATIVE_PATH_TO/vendor/igaster/laravel_cities/src/vue/geo-select.vue'));`

Alternative you may publish the component with

`php artisan vendor:publish --provider="Igaster\LaravelCities\GeoServiceProvider"` and edit it.

now your component's path should be registered as

`Vue.component('geo-select', require('RELATIVE_PATH_TO/resources/LaravelCities/geo-select.vue'));`

### Compile compoment

`npm run dev`

### Use in blade files

Example:
```html
<form action="post-url" method="POST">
	<geo-select></geo-select>
	<input type="submit">
</form>
```

The following inputs will be submited:

- geo-id
- geo-name
- geo-long
- geo-lat
- geo-country
- geo-country-code
