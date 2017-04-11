[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg)](https://tldrlegal.com/license/mit-license)

# Introduction

What you get:
- Deploy and use geonames.org (ex MaxCDN) database localy to query countries/cities
- Get information like lattitude/longtityde, population etc 
- Optimized [DB tree structure](https://en.wikipedia.org/wiki/Nested_set_model) for searching and traversing the tree.
- Provides an Eloquent model (geo) with multiple query-scopes to help you build your queries.
- Exposes a simple API that you can use to create AJAX calls. (Eg search while typing etc).
- A sample vue.js component that that can be inserted into your forms and provides a UI to pick a location

What you dont get:
- geoIP & Postalcodes (not included in free sets)
- Map elements smaller than "3rd Administration Division" (=Cities)

# Instructions
	
- Create a folder `geo` into app's storage folder ('\storage\geo')
- Download & unzip "hieararcy.txt" & "allCountries.txt" from geonames.org (http://download.geonames.org/export/dump)

[Tip] Quick script to download on your remote server with:

```
mkdir storage\geo
cd storage\geo
wget http://download.geonames.org/export/dump/allCountries.zip && unzip allCountries.zip && rm allCountries.zip
wget http://download.geonames.org/export/dump/hierarchy.zip && unzip hierarchy.zip && rm hierarchy.zip
```

- Install with copmoser. Run:

`composer require igaster/laravel_cities`

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

# Seed with custom data

Create a json file with custom data at `storage\geo` and seed with:

```
artisan geo:json FILENAME
```

If an item exists in the DB (based on the 'id' value), then it will be updated else a new entry will be inserted. See the example [allCountries.json](https://github.com/igaster/laravel_cities/data/allCountries.json)

Tip: You can get a json representation from the DB by quering the API (see below)

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

Geo::getCountries();               // Get a Collection of all countries
Geo::getCountry('US');             // Get item by Country code
Geo::findName('Nomos Kerkyras');   // Find item by (ascii) name
Geo::searchNames('york');          // Search item by all alternative names. Case insensitive 
Geo::searchNames('vegas', Geo::getCountry('US'));  // ... and belongs to an item
Geo::getByIds([390903,3175395]);   // Get a Collection of items by Ids
```

## Traverse tree
```php
$children    = $geo->getChildren();    // Get direct Children of $geo (Collection)
$parent      = $geo->getParent();      // Get single Parent of $geo (Geo)
$ancenstors  = $geo->getAncensors();   // Get Ancenstors tree of $geo from top->bottom (Collection)
$descendants = $geo->getDescendants(); // Get all Descentants of $geo alphabetic (Collection)
```


## Check Hierarchy Relations:
```php
$geo1->isParentOf($geo2);       // (Bool) Check if $geo2 is direct Parent of $geo1
$geo2->isChildOf($geo1);        // (Bool) Check if $geo2 is direct Child of $geo1
$geo1->isAncenstorOf($geo2);    // (Bool) Check if $geo2 is Ancenstor of $geo1
$geo2->isDescendantOf($geo1);   // (Bool) Check if $geo2 is Descentant of $geo1
```

## Query scopes (Use them to Build custom queries)
```php
Geo::level($level);     // Filter by Administration level: 
                        // Geo::LEVEL_COUNTRY, Geo::LEVEL_CAPITAL, Geo::LEVEL_1, Geo::LEVEL_2, Geo::LEVEL_3
Geo::country('US');     // (Shortcut) Items that belongs to country US 
Geo::capital();         // (Shortcut) Items that are capitals
Geo::search($name);     // Items that conain $name in name OR alternames (Case InSensitive)
Geo::areDescentants($geo);   // Items that belong to $geo

$geo->ancenstors();     // Items that contain $geo
$geo->descendants();    // Items that belong to $geo
$geo->children();       // Items that are direct children of $geo


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
|api/geo/items/{ids}                | Get multiple items by ids (comma seperated list)          | Collection     |
|api/geo/children/{id}              | Get children of item                                      | Collection     |
|api/geo/parent/{id}                | Get parent of item                                        | Geo            |
|api/geo/country/{code}             | get country by two-letter code                            | Geo            |
|api/geo/countries                  | list of countries                                         | Collection     |

The response is always a JSON representation of either a Geo class or a Collection.

To reduce bandwith, all Geo model attributes will be returned except from `alternames`, `left`, `right` and `depth`. You can change this behavior by passing an optional parameter on any request:

| URL Params (aplly to all routes)  | Description                             | Example                         |
|-----------------------------------|-----------------------------------------|---------------------------------|
|fields=field1,field2               | Returns only the specified attributes   | api/geo/countries?fields=id,name|
|fields=all                         | Returns all attributes                  | api/geo/countries?fields=all    |


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

`artisan vendor:publish --provider="Igaster\LaravelCities\GeoServiceProvider"`

Component will be exported at `/resources/LaravelCities/geo-select.vue` so that you can make modifications...

### Compile compoment

`npm run dev` (or `npm run production`)

### Use in blade files

Example:
```html
<form action="post-url" method="POST">
	<geo-select></geo-select>
	<!-- Add more form fields here... -->
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

### Full syntax:

```html
<geo-select
	prefix = "geo"                 		<!-- prefix fields that will be submited --> 
	api-root-url = "\api"          		<!-- Root url for API -->
	:countries = "[390903,3175395]"		<!-- Limit to specific countries (defined by ids) -->
	:enable-breadcrumb = "true"			<!-- Enable/Disable Breadcrumb -->
></geo-select>
```
