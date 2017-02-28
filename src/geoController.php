<?php namespace igaster\laravel_cities;

class geoController extends \Illuminate\Routing\Controller {

	public function item($id){
		return \Response::json(geo::find($id));
	}

	public function children($id){
		return geo::find($id)->getChildren();
	}

	public function parent($id){
		return \Response::json(geo::find($id)->getParent());
	}

	public function country($code){
		return \Response::json(geo::getCountry($code));
	}

	public function countries(){
		return geo::level(geo::LEVEL_COUNTRY)->get();
	}

	public function search($name,$parent_id = null){
		if ($parent_id)
			return geo::searchNames($name, geo::find($parent_id));
		else
			return geo::searchNames($name);
	}

	public function test($id){
		return \View::make('laravel_cities::test', [
			'item' => geo::find($id),
		]);
	}

}
