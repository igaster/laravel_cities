<?php

namespace Igaster\LaravelCities\Tests;

use Igaster\LaravelCities\Geo;

class geoTest extends abstractTest
{
    //-- Test: testDummy
    public function testDummy()
    {
        // $results = Geo::findName('Ionian Islands')->getChildren();
        // dd($results->map(function($item){return $item->name;}));
        $this->assertTrue(true);
    }

    //-- Test: is instance of \Igaster\LaravelCities\geo
    public function testModel()
    {
        $model = Geo::create();
        $this->reloadModel($model);
        $this->assertInstanceOf(Geo::class, $model);
    }

    //-- Test: Get the Capital of Greece
    public function testCapitalGr()
    {
        $item = Geo::country('GR')->capital()->get();
        $this->assertEquals(1, $item->count());

        $item = Geo::country('GR')->capital()->first();
        $this->assertEquals('Athens', $item->name);
    }

    //-- Test: Get the Capital of USA
    public function testCapitalUs()
    {
        $item = Geo::country('US')->capital()->get();
        $this->assertEquals(1, $item->count());

        $item = Geo::country('US')->capital()->first();
        $this->assertEquals('Washington, D.C.', $item->name);
    }

    //-- Test: isParentOf() isChildOf()
    public function testRelationParentChildren()
    {
        $geo1 = Geo::findName('Ionian Islands');
        $geo2 = Geo::findName('Nomos Kerkyras');
        $geo3 = Geo::findName('Dimos Corfu');

        $this->assertTrue($geo1->isParentOf($geo2));
        $this->assertTrue($geo2->isParentOf($geo3));

        $this->assertFalse($geo1->isParentOf($geo3));
        $this->assertFalse($geo2->isParentOf($geo1));

        $this->assertTrue($geo2->isChildOf($geo1));
        $this->assertTrue($geo3->isChildOf($geo2));
        $this->assertFalse($geo3->isChildOf($geo1));
        $this->assertFalse($geo1->isChildOf($geo2));
    }

    //-- Test: isAncestorOf() isDescendantOf()
    public function testRelationAncestorDescendant()
    {
        $geo1 = Geo::findName('Ionian Islands');
        $geo2 = Geo::findName('Nomos Kerkyras');
        $geo3 = Geo::findName('Dimos Corfu');

        $this->assertTrue($geo1->isAncestorOf($geo2));
        $this->assertTrue($geo2->isAncestorOf($geo3));
        $this->assertTrue($geo1->isAncestorOf($geo3));
        $this->assertFalse($geo2->isAncestorOf($geo1));

        $this->assertTrue($geo2->isDescendantOf($geo1));
        $this->assertTrue($geo3->isDescendantOf($geo2));
        $this->assertTrue($geo3->isDescendantOf($geo1));
        $this->assertFalse($geo1->isDescendantOf($geo2));
    }

    //-- Test: getChildren(), getParent(), getAncestors()
    public function testTravelTree()
    {
        $children = Geo::findName('Ionian Islands')->getChildren();

        $this->assertTrue($children->contains('name', 'Nomos Kerkyras'));
        $this->assertTrue($children->contains('name', 'Nomos Zakynthou'));
        $this->assertFalse($children->contains('name', 'Dimos Corfu'));

        $children = Geo::findName('Nomos Kerkyras')->getChildren();
        $this->assertFalse($children->contains('name', 'Nomos Zakynthou'));
        $this->assertTrue($children->contains('name', 'Dimos Corfu'));

        $parent = Geo::findName('Nomos Kerkyras')->getParent();
        $this->assertEquals('Ionian Islands', $parent->name);

        $ancestors = Geo::findName('Dimos Corfu')->getAncestors();

        $this->assertEquals('Hellenic Republic', $ancestors[0]->name);
        $this->assertEquals('Ionian Islands', $ancestors[1]->name);
        $this->assertEquals('Nomos Kerkyras', $ancestors[2]->name);
    }

    //-- Test: JSON field alternames returns an Array
    public function testAlternateNamesIsArray()
    {
        $this->assertIsArray(Geo::findName('Nomos Kerkyras')->alternames);
    }

    //-- Test: searchNames($string)
    public function testSearchAlternateNames()
    {
        $results = Geo::searchNames('Κέρκυρα');

        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Nomos Kerkyras';
        }));

        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Dimos Corfu';
        }));


        $results = Geo::searchNames('Samou');
        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Nomos Samou';
        }));

        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Dimos Samos';
        }));
    }

    // -- Test: searchNames($string)
    public function testSearchAlternateNamesNotCaseSensitive()
    {
        $results = Geo::searchNames('έΡκυΡ');

        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Nomos Kerkyras';
        }));
    }

    //-- Test: Geo::searchNames($string, $parent)
    public function testSearchAlternateNamesWithParent()
    {

        $results1 = Geo::searchNames('Samou');
        $results2 = Geo::searchNames('Samou', Geo::getCountry('GR'));

        $this->assertTrue($results1->contains(function ($item) {
            return $item->name == 'Nomos Samou';
        }));

        $this->assertTrue($results1->contains(function ($item) {
            return $item->name == 'Muang Samouay';
        }));

        $this->assertTrue($results2->contains(function ($item) {
            return $item->name == 'Nomos Samou';
        }));

        $this->assertFalse($results2->contains(function ($item) {
            return $item->name == 'Muang Samouay';
        }));
    }


    //-- Test: searchNames() return partial matches (LIKE sql operator)
    public function testSearchAlternateNamesLikeOperator()
    {
        $results = Geo::searchNames('έρκ', Geo::getCountry('GR'));

        $this->assertTrue($results->contains(function ($item) {
            return $item->name == 'Nomos Kerkyras';
        }));
    }

    //-- Test: getChildren()
    public function testGetChildren()
    {
        $result = Geo::findName('Ionian Islands')->getChildren();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        $result = $result->map(function ($item) {
            return $item->name;
        })->toArray();

        $this->assertEquals([
            0 => "Lefkada",
            1 => "Nomos Kefallinias",
            2 => "Nomos Kerkyras",
            3 => "Nomos Zakynthou",
        ], $result);
    }

    //-- Test: getDescendants()
    public function testGetDescendants()
    {
        $result = Geo::findName('Ionian Islands')->getDescendants();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        $result = $result->map(function ($item) {
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
        ], $result);
    }


    //-- Test: getByIds()
    public function testGetByIds()
    {
        $result = Geo::getByIds([6252001, 390903, 3175395]); // US,GR,IT

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        $result = $result->map(function ($item) {
            return $item->name;
        })->toArray();

        $this->assertEquals([
            0 => 'Hellenic Republic',
            1 => 'Italian Republic',
            2 => 'United States',
        ], $result);
    }

    //-- Test: setJsonFields()

    protected function assertArrayHasKeysOnly($keys = [], $array = [])
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        $this->assertEquals(count($keys), count($array));
    }

    public function testSetJsonFields()
    {
        $result = Geo::findName('Ionian Islands')->toArray();
        $this->assertArrayHasKeysOnly([
            'id',
            'parent_id',
            // 'left',
            // 'right',
            // 'depth',
            'name',
            // 'alternames',
            'country',
            'level',
            'population',
            'lat',
            'long',
        ], $result);

        $result = Geo::findName('Ionian Islands')->filterFields(['name', 'country'])->toArray();
        $this->assertArrayHasKeysOnly([
            'name',
            'country',
        ], $result);

        $result = Geo::findName('Ionian Islands')->filterFields()->toArray();
        $this->assertArrayHasKeysOnly([
            'id',
            'parent_id',
            'left',
            'right',
            'depth',
            'name',
            'alternames',
            'country',
            'level',
            'population',
            'lat',
            'long',
        ], $result);
    }

    // public function testRebuildTree(){
    // 	Geo::rebuildTree(true);
    // 	$this->assertTrue(true);
    // }

}