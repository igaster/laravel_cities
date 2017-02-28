<?php

Route::group(['prefix' => 'api/geo', 'middleware' => 'api'], function(){
	Route::get('search/{name}/{parent_id?}', 	'igaster\laravel_cities\geoController@search');
	Route::get('item/{id}', 		'igaster\laravel_cities\geoController@item');
	Route::get('children/{id}', 	'igaster\laravel_cities\geoController@children');
	Route::get('parent/{id}', 		'igaster\laravel_cities\geoController@parent');
	Route::get('country/{code}', 	'igaster\laravel_cities\geoController@country');
	Route::get('countries', 		'igaster\laravel_cities\geoController@countries');
});

Route::group(['prefix' => 'geo', 'middleware' => 'web'], function(){
	Route::get('test/{id}', 	'igaster\laravel_cities\geoController@test');
});