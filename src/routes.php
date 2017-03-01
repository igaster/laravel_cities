<?php

Route::group(['prefix' => 'api/geo', 'middleware' => 'api'], function(){
	Route::get('search/{name}/{parent_id?}', 	'Igaster\LaravelCities\GeoController@search');
	Route::get('item/{id}', 		'Igaster\LaravelCities\GeoController@item');
	Route::get('children/{id}', 	'Igaster\LaravelCities\GeoController@children');
	Route::get('parent/{id}', 		'Igaster\LaravelCities\GeoController@parent');
	Route::get('country/{code}', 	'Igaster\LaravelCities\GeoController@country');
	Route::get('countries', 		'Igaster\LaravelCities\GeoController@countries');
});

