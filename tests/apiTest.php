<?php

use Igaster\LaravelCities\Geo;

class apiTest extends abstractTest
{

    // -----------------------------------------------
    //   add Service Providers & Facades
    // -----------------------------------------------

    protected function getPackageProviders($app) {
    	// Register API routes
		Geo::ApiRoutes();

		// Register Service providers
        return [
            \Igaster\LaravelCities\geoServiceProvider::class,
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

	public function testDummy(){
		// dd($results->map(function($item){return $item->name;}));
		$this->assertTrue(true);
	}

	public function testCountry(){
		$this->assertTrue(true);
		$result = $this->api('/geo/country/gr');
		$this->assertEquals('Hellenic Republic', $result->name);

		$result = $this->api('/geo/country/GR');
		$this->assertEquals('Hellenic Republic', $result->name);
	}

	public function testParent(){
		$geo = Geo::findName('Nomos Kerkyras');
		$result = $this->api("/geo/parent/{$geo->id}");
		$this->assertEquals('Ionian Islands', $result->name);
	}

	public function testItem(){
		$geo = Geo::findName('Nomos Kerkyras');
		$result = $this->api("/geo/item/{$geo->id}");
		$this->assertEquals('Nomos Kerkyras', $result->name);
	}

	public function testCountries(){
		$result = $this->api("/geo/countries");
		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Hellenic Republic', $result);
		$this->assertContains('Repubblica Italiana', $result);
	}


	public function testChildren(){
		$geo = Geo::findName('Nomos Kerkyras');
		$result = $this->api("/geo/children/{$geo->id}");

		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Dimos Corfu', $result);
		$this->assertContains('Dimos Paxoi', $result);
	}


	public function testSearch(){
		$result = $this->api("/geo/search/κέρκΥρ");

		$result = array_map(function($item){
			return $item->name;
		}, $result);

		$this->assertContains('Dimos Corfu', $result);
	}

}