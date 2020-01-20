<?php

namespace Igaster\LaravelCities\commands\helpers;

class geoCollection
{
    public $items = [];

    public function add($item)
    {
        $this->items[$item->getId()] = $item;
    }

    public function findGeoId($geoId)
    {
        return isset($this->items[$geoId]) ? $this->items[$geoId] : null;
    }

    public function findId($id)
    {
        foreach ($this->items as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }
        return false;
    }

    public function findName($name)
    {
        foreach ($this->items as $item) {
            if ($item->data[2] == $name) {
                return $item;
            }
        }
        return false;
    }
    
    public function reset()
    {
        $this->items = [];
        return $this;
    }
}
