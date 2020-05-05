[![License](http://img.shields.io/badge/license-MIT-orange.svg)](https://tldrlegal.com/license/mit-license)
[![Downloads](https://img.shields.io/packagist/dt/igaster/laravel_cities.svg?style=flat-square)](https://packagist.org/packages/igaster/laravel_cities)

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
	
- Install with copmoser. Run:

`composer require igaster/laravel_cities`

The Service provider will be autodiscovered and registered by Laravel. If you are using Laravel version <5.5 then you  must manually add the Service Provider in app.php:

```php
'providers' => [
    //...
    Igaster\LaravelCities\GeoServiceProvider::class,
];
```

- Create a folder `geo` into app's storage folder ('\storage\geo'). Download & unzip "hieararcy.txt" & "allCountries.txt" from geonames.org (http://download.geonames.org/export/dump)

[Tip] Quick script to download on your remote server with:

```
mkdir -p storage/geo && cd storage/geo
wget http://download.geonames.org/export/dump/allCountries.zip && unzip allCountries.zip && rm allCountries.zip
wget http://download.geonames.org/export/dump/hierarchy.zip && unzip hierarchy.zip && rm hierarchy.zip
```

or otherwise you can use 
```
artisan geo:download
```

Download a *.txt files from geonames.org By default it will download allcountries and hierarchy files otherwise you can pass flag --countries for specific countries

- Migrate and Seed. Run:

```
artisan migrate
artisan geo:seed
```

you can increase the memory limit for the cli invocation on demand to have process the command at once
```
php -d memory_limit=8000M artisan geo:seed --chunk=100000
```
So this will increase the memory limit for the command to 8GB with large chunk for each batches

You can also pass `--chunk` argument to specify how much chunk you want to process at once suppose you want `3000` records to be processed at once you can pass.
This gives flexibility to make the import with low memory footprints
```
artisan geo:seed --chunk=3000
```
by default it is `1000`

Note: If you don't want all the countries, you can download only country specific files (eg US.txt) and import each one of them with:

```
artisan geo:seed US --append
```

# Seed with custom data

Create a json file with custom data at `storage\geo` and run the following command to pick a file to seed:

```
artisan geo:json
```

If an item exists in the DB (based on the 'id' value), then it will be updated else a new entry will be inserted. For example the following json file will rename `United States` to `USA` and it will add a child item (set by the parent_id value)

```json
[
  {
    "id": 6252001,
    "name": "USA"
  },
  {
    "name": "USA Child Item",
    "parent_id": 6252001,
    "alternames": ["51st State", "dummy name"],
    "population": 310232863,
    "lat": "39.760000",
    "long": "-98.500000"
  }
]
```
Please note that adding new items to the DB will reindex ALL items to rebuild the tree structure. Please be patient...

An example file is provided: [countryNames.json](https://github.com/igaster/laravel_cities/blob/master/data/countryNames.json) which updates the official  country names with a most popular simplified version.

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
$ancestors  = $geo->getAncestors();   // Get Ancestors tree of $geo from top->bottom (Collection)
$descendants = $geo->getDescendants(); // Get all Descendants of $geo alphabetic (Collection)
```


## Check Hierarchy Relations:
```php
$geo1->isParentOf($geo2);       // (Bool) Check if $geo2 is direct Parent of $geo1
$geo2->isChildOf($geo1);        // (Bool) Check if $geo2 is direct Child of $geo1
$geo1->isAncestorOf($geo2);    // (Bool) Check if $geo2 is Ancestor of $geo1
$geo2->isDescendantOf($geo1);   // (Bool) Check if $geo2 is Descendant of $geo1
```

## Query scopes (Use them to Build custom queries)
```php
Geo::level($level);     // Filter by Administration level: 
                        // Geo::LEVEL_COUNTRY, Geo::LEVEL_CAPITAL, Geo::LEVEL_1, Geo::LEVEL_2, Geo::LEVEL_3
Geo::country('US');     // (Shortcut) Items that belongs to country US 
Geo::capital();         // (Shortcut) Items that are capitals
Geo::search($name);     // Items that conain $name in name OR alternames (Case InSensitive)
Geo::areDescendants($geo);   // Items that belong to $geo

$geo->ancestors();     // Items that contain $geo
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


# Alternate names

If you need localization/internationalization you can use the alternate names table. This is not loaded by default.

- Download with alternate names:
```
artisan geo:download --alternate
```

- Seed. Run:
```
artisan geo:alternate
```

Like before, you can increase the memory limit for the cli invocation on demand to have process the command at once.
```
php -d memory_limit=8000M artisan geo:alternate --chunk=100000
```

## Filters 

On the HTTP API you now have a few query options to return alternate names:

| URL Params (aplly to all routes)      | Description                            | Example                                                  |
|---------------------------------------|----------------------------------------|----------------------------------------------------------|
|geoalternate=true                      | Returns the alternate names            | api/geo/countries?geoalternate=true                      |
|geoalternate=true&isolanguage=x        | Returns only English alternate names   | api/geo/countries?geoalternate=true&isolanguage=pt,br    |
|geoalternate=true&isPreferredName=true | Returns only preferred names           | api/geo/countries?geoalternate=true&isPreferredName=true |
|geoalternate=true&isShortName=true     | Returns only short names               | api/geo/countries?geoalternate=true&isShortName=true     |

`geoalternate=true` is mandatory to return alternate names, the other options can be combined if you want to filter. `isolanguages` accepts multiple languages separated by comma.

Results are returned in a `geoalternate` key. It's an array of object, whose fields are the following:

```
alternateNameId   : the id of this alternate name, int
geonameid         : geonameId referring to id in table 'geoname', int
isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link to a website (mostly to wikipedia), wkdt for the wikidataid, varchar(7)
alternate name    : alternate name or name variant, varchar(400)
isPreferredName   : '1', if this alternate name is an official/preferred name, int
isShortName       : '1', if this is a short name like 'California' for 'State of California', int
isColloquial      : '1', if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York', int
isHistoric        : '1', if this alternate name is historic and was used in the past. Example 'Bombay' for 'Mumbai', int
from		  : from period when the name was used
to		  : to period when the name was used
```

Avoid getting all the geoalternate names without extra filters, as it can be a lot of data.

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
	prefix = "geo"                    <!-- change the  fields name prefix --> 
	api-root-url = "\api"             <!-- Root url for API -->
	:countries = "[390903,3175395]"   <!-- Limit to specific countries (defined by ids) -->
	:enable-breadcrumb = "true"       <!-- Enable/Disable Breadcrumb -->
></geo-select>
```

