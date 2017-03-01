<?php

Route::group(['prefix' => 'api/geo', 'middleware' => 'api'], function(){
	Route::get('search/{name}/{parent_id?}', 	'Igaster\LaravelCities\geoController@search');
	Route::get('item/{id}', 		'Igaster\LaravelCities\geoController@item');
	Route::get('children/{id}', 	'Igaster\LaravelCities\geoController@children');
	Route::get('parent/{id}', 		'Igaster\LaravelCities\geoController@parent');
	Route::get('country/{code}', 	'Igaster\LaravelCities\geoController@country');
	Route::get('countries', 		'Igaster\LaravelCities\geoController@countries');
});

