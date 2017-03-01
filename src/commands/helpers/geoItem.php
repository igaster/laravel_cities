<?php namespace Igaster\LaravelCities\commands\helpers;

class geoItem {

	public $data;
	public $id;

	public $parent = null;
	public $parentId = null;
	public $childrenGeoId = [];
	public $depth = 0;

	public $left = null;
	public $right = null;

	private $geoItems;

	function __construct( $rawData, $geoItems ) {
		$rawData[3] = json_encode(str_getcsv($rawData[3]),JSON_UNESCAPED_UNICODE);
		$this->data=$rawData;
		$this->geoItems=$geoItems;
	}

	public function getId(){
		return $this->data[0];
	}

	public function getName(){
		return $this->data[2];
	}

	public function setParent($geoId){
		if( $parent = $this->geoItems->findGeoId($geoId)){
			$this->parent = $geoId;
			$this->parentId = $parent->id;
			
		}
	}

	public function addChild($geoId){
		$this->childrenGeoId[] = $geoId;
	}

	public function getChildren(){
		$results = [];
		foreach ($this->childrenGeoId as $geoId) {
			$results[] = $this->geoItems->findGeoId($geoId);	
		}
		return $results;
	}
}
