<?php namespace igaster\laravel_cities\commands\helpers;

class geoCollection {
	public $items = [];
	public $count = 0;

	public function __construct($startId = 0){
		$this->count = $startId;
	}

	public function add($item){
		$this->count++;
		$item->id = $this->count;
		$this->items[$item->getId()] = $item;
	}

	public function findGeoId($geoId){
		return isset($this->items[$geoId]) ? $this->items[$geoId] : null;
	}

	public function findId($id){
		foreach ($this->items as $item) {
			if($item->id == $id)
				return $item;
		}
		return false;
	}


	public function findName($name){
		foreach ($this->items as $item) {
			if($item->data[2] == $name)
				return $item;
		}
		return false;
	}
}
