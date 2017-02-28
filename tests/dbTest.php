<?php

use igaster\laravel_cities\geo;

class dbTest extends abstractTest
{

	public function testNomoi() {
		$result = $this->sql('SELECT COUNT(*) as counter FROM geo WHERE Country="GR" AND level="ADM2"');
		$this->assertEquals($result['counter'], 55);
	}

	public function testCapital() {
		$result = $this->sql('SELECT COUNT(*) as counter FROM geo WHERE Country="GR" AND level="PPLC"');
		$this->assertEquals($result['counter'], 1);

		$result = $this->sql('SELECT * FROM geo WHERE Country="GR" AND level="PPLC"');
		$this->assertEquals($result['name'], 'Athens');
	}

}