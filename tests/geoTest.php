<?php

use igaster\laravel_cities\geo;

class geoTest extends abstractTest
{
	//-- Test: testDummy
	public function testDummy(){
		// $results = geo::findName('Ionian Islands')->getChildren();
		// dd($results->map(function($item){return $item->name;}));
		$this->assertTrue(true);
	}

	//-- Test: is instance of \igaster\laravel_cities\geo
	public function testModel(){
		$model = geo::create();
		$this->reloadModel($model);
		$this->assertInstanceOf(geo::class, $model);
	}

	//-- Test: Get the Capital of Greece
	public function testCapitalGr(){
		$item = geo::country('GR')->capital()->get();
		$this->assertEquals(1,  $item->count());

		$item = geo::country('GR')->capital()->first();
		$this->assertEquals('Athens',  $item->name);
	}

	//-- Test: Get the Capital of USA
	public function testCapitalUs(){
		$item = geo::country('US')->capital()->get();
		$this->assertEquals(1,  $item->count());

		$item = geo::country('US')->capital()->first();
		$this->assertEquals('Washington, D.C.',  $item->name);
	}

	//-- Test: isParentOf() isChildOf()
	public function testRelationParentChildren(){
		$geo1  = geo::findName('Ionian Islands'); 
		$geo2 = geo::findName('Nomos Kerkyras');
		$geo3 = geo::findName('Dimos Corfu');
		
		$this->assertTrue($geo1->isParentOf($geo2));
		$this->assertTrue($geo2->isParentOf($geo3));

		$this->assertFalse($geo1->isParentOf($geo3));
		$this->assertFalse($geo2->isParentOf($geo1));

		$this->assertTrue($geo2->isChildOf($geo1));
		$this->assertTrue($geo3->isChildOf($geo2));
		$this->assertFalse($geo3->isChildOf($geo1));
		$this->assertFalse($geo1->isChildOf($geo2));
	}

	//-- Test: isAncenstorOf() isDescendantOf()
	public function testRelationAncenstorDescentant(){
		$geo1  = geo::findName('Ionian Islands'); 
		$geo2 = geo::findName('Nomos Kerkyras');
		$geo3 = geo::findName('Dimos Corfu');
		
		$this->assertTrue($geo1->isAncenstorOf($geo2));
		$this->assertTrue($geo2->isAncenstorOf($geo3));
		$this->assertTrue($geo1->isAncenstorOf($geo3));
		$this->assertFalse($geo2->isAncenstorOf($geo1));

		$this->assertTrue($geo2->isDescendantOf($geo1));
		$this->assertTrue($geo3->isDescendantOf($geo2));
		$this->assertTrue($geo3->isDescendantOf($geo1));
		$this->assertFalse($geo1->isDescendantOf($geo2));
	}

	//-- Test: getChildren(), getParent(), getAncensors()
	public function testTravelTree(){
		$children = geo::findName('Ionian Islands')->getChildren();

		$this->assertTrue($children->contains('name','Nomos Kerkyras'));
		$this->assertTrue($children->contains('name','Nomos Zakynthou'));
		$this->assertFalse($children->contains('name','Dimos Corfu'));

		$children = geo::findName('Nomos Kerkyras')->getChildren();
		$this->assertFalse($children->contains('name','Nomos Zakynthou'));
		$this->assertTrue($children->contains('name','Dimos Corfu'));

		$parent = geo::findName('Nomos Kerkyras')->getParent();
		$this->assertEquals('Ionian Islands', $parent->name);

		$ancenstors = geo::findName('Dimos Corfu')->getAncensors();

		$this->assertEquals('Hellenic Republic', $ancenstors[0]->name);
		$this->assertEquals('Ionian Islands', $ancenstors[1]->name);
		$this->assertEquals('Nomos Kerkyras', $ancenstors[2]->name);
	}

	//-- Test: JSON field alternames returns an Array
	public function testAlternateNamesIsArray(){
		$this->assertInternalType('array', geo::findName('Nomos Kerkyras')->alternames);
	}

	//-- Test: searchNames($string)
	public function testSearchAlternateNames(){
		$results = geo::searchNames('Κέρκυρα');

		$this->assertTrue($results->contains(function($item){
			return $item->name=='Nomos Kerkyras';
		}));

		$this->assertTrue($results->contains(function($item){
			return $item->name=='Dimos Corfu';
		}));


		$results = geo::searchNames('Samou');
		$this->assertTrue($results->contains(function($item){
			return $item->name=='Nomos Samou';
		}));

		$this->assertTrue($results->contains(function($item){
			return $item->name=='Dimos Samos';
		}));
	}

	//-- Test: searchNames($string)
	// public function testSearchAlternateNamesNotCaseSensitive(){
	// 	$results = geo::searchNames('ΚέΡκυΡα');

	// 	$this->assertTrue($results->contains(function($item){
	// 		return $item->name=='Nomos Kerkyras';
	// 	}));
	// }

	//-- Test: geo::searchNames($string, $parent)
	public function testSearchAlternateNamesWithParent(){

		$results1 = geo::searchNames('Samou');
		$results2 = geo::searchNames('Samou', geo::getCountry('GR'));

		$this->assertTrue($results1->contains(function($item){
			return $item->name=='Nomos Samou';
		}));

		$this->assertTrue($results1->contains(function($item){
			return $item->name=='Muang Samouay';
		}));

		$this->assertTrue($results2->contains(function($item){
			return $item->name=='Nomos Samou';
		}));

		$this->assertFalse($results2->contains(function($item){
			return $item->name=='Muang Samouay';
		}));
	}


	//-- Test: searchNames() return partial matches (LIKE sql operator)
	public function testSearchAlternateNamesLikeOperator(){
		$results = geo::searchNames('έρκ', geo::getCountry('GR'));
		
		$this->assertTrue($results->contains(function($item){
			return $item->name=='Nomos Kerkyras';
		}));
	}

	//-- Test: getChildren()
	public function testGetChildren(){
		$result = geo::findName('Ionian Islands')->getChildren();

		$this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
			
		$result= $result->map(function($item){
			return $item->name;
		})->toArray();

		$this->assertEquals([
			0 => "Lefkada",
			1 => "Nomos Kefallinias",
			2 => "Nomos Kerkyras",
			3 => "Nomos Zakynthou",
		],$result);
	}

	//-- Test: getDescendants()
	public function testGetDescendants(){
		$result = geo::findName('Ionian Islands')->getDescendants();

		$this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
			
		$result= $result->map(function($item){
			return $item->name;
		})->toArray();

		$this->assertEquals([
 			0 => "Lefkada",
 			1 => "Nomos Kefallinias",
 			2 => "Nomos Kerkyras",
 			3 => "Nomos Zakynthou",
 			4 => "Dimos Corfu",
 			5 => "Dimos Ithaca",
 			6 => "Dimos Kefalonia",
 			7 => "Dimos Lefkada",
 			8 => "Dimos Meganisi",
 			9 => "Dimos Paxoi",
 			10 => "Dimos Zakynthos",
		],$result);
	}
}