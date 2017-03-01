<?php namespace igaster\laravel_cities;

class geoController extends \Illuminate\Routing\Controller {

	public function item($id){
		return \Response::json(Geo::find($id));
	}

	public function children($id){
		return Geo::find($id)->getChildren();
	}

	public function parent($id){
		return \Response::json(Geo::find($id)->getParent());
	}

	public function country($code){
		return \Response::json(Geo::getCountry($code));
	}

	public function countries(){
		return Geo::level(Geo::LEVEL_COUNTRY)->get();
	}

	public function search($name,$parent_id = null){
		if ($parent_id)
			return Geo::searchNames($name, Geo::find($parent_id));
		else
			return Geo::searchNames($name);
	}

}
