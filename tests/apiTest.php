<?php

use igaster\laravel_cities\geo;

class apiTest extends abstractTest
{

    // -----------------------------------------------
    //   add Service Providers & Facades
    // -----------------------------------------------

    protected function getPackageProviders($app) {
        return [
            \igaster\laravel_cities\geoServiceProvider::class,
        ];
    }

    // -----------------------------------------------
    //   Helpers
    // -----------------------------------------------

    protected function api($url) {
		$request = Request::create($url, 'GET');
		$response = \App::handle($request);
		if($response->status()!==200){
			throw new Exception("API Call to [$url] returned status code ".$response->status()."\n--------------[Output]--------------\n".strip_tags(substr($response->getContent(), strpos($response->getContent(), '<body'))), 1);
		}
		return json_decode($response->getContent());
    }    

    // -----------------------------------------------
    //  Tests
    // -----------------------------------------------

	//-- Test: testDummy
	public function testDummy(){
		// dd($results->map(function($item){return $item->name;}));
		$this->assertTrue(true);
	}

	// Route::get('search/{name}/{parent_id?}', 	'igaster\laravel_cities\geoController@search');

	public function testCountry(){
		$result = $this->api('/api/geo/country/gr');
		$this->assertEquals('Hellenic Republic', $result->name);

		$result = $this->api('/api/geo/country/GR');
		$this->assertEquals('Hellenic Republic', $result->name);
	}

	public function testParent(){
		$geo = geo::findName('Nomos Kerkyras');
		$result = $this->api("/api/geo/parent/{$geo->id}");
		$this->assertEquals('Ionian Islands', $result->name);
	}

	public function testItem(){
		$geo = geo::findName('Nomos Kerkyras');
		$result = $this->api("/api/geo/item/{$geo->id}");
		$this->assertEquals('Nomos Kerkyras', $result->name);
	}

	public function testCountries(){
		$result = $this->api("/api/geo/countries");
		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Hellenic Republic', $result);
		$this->assertContains('Repubblica Italiana', $result);
	}


	public function testChildren(){
		$geo = geo::findName('Nomos Kerkyras');
		$result = $this->api("/api/geo/children/{$geo->id}");

		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Dimos Corfu', $result);
		$this->assertContains('Dimos Paxoi', $result);
	}


	public function testSearch(){
		$result = $this->api("/api/geo/search/κέρκΥρ");

		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Dimos Corfu', $result);
	}

}