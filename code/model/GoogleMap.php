<?php

class GoogleMap extends DataObject {
	/**
	 * Store the (optional) maps API key
	 */
	static $api_key = false;

	static $db = array(
		'Address' => 'Text',
		'CenterLat' => 'Decimal(20,14)',
		'CenterLng' => 'Decimal(20,14)',
		'MarkerLat' => 'Decimal(20,14)',
		'MarkerLng' => 'Decimal(20,14)',
		'Zoom' => 'Int',
		'MarkerColour' => 'Varchar(25)',
		'MarkerLabel' => 'Varchar(255)',
		'MapType' => 'Enum("roadmap,satellite,hybrid,terrain", "roadmap")'
	);

	static $defaults = array(
		'MarkerColour' => 'red',
		'MapType' => 'roadmap',
		'Zoom' => 4
	);

	/**
	 * Default width used for static maps
	 * @var integer
	 */
	static $default_width = 300;


	/**
	 * Default height used for static maps
	 * @var integer
	 */
	static $default_height = 300;


	/**
	 * Whether to include the frontend script
	 * @var boolean
	 */
	static $include_frontend_script = true;


	/**
	 * Whether to include the API script
	 * @var boolean
	 */
	static $include_api_script = true;


	public static $static_map_root = 'http://maps.googleapis.com/maps/api/staticmap';

	/**
	 * Generates the URL for a static version of the map
	 * @param  int $width  Width of the returned image (defaults to self::$default_width)
	 * @param  int $height Height of the returned image (defaults to self::$default_height)
	 * @return string         URL that can be used an an <img> src
	 */
	function getStaticMapLink($width = null, $height = null) {
		$output = self::$static_map_root . '?';
		$params = array();

		$params[] = 'center='.$this->CenterLat.','.$this->CenterLng;
		$params[] = 'zoom='.$this->Zoom;

		if ($width) {
			if (!$height) {
				$height = $width;
			}
			
		} else {
			$width = self::$default_width;
			$height = self::$default_height;
		}

		$params[] = 'size='.$width.'x'.$height;
		$params[] = 'maptype='.$this->MapType;

		if ($this->MarkerLat) {
			$markerParam = array();


			if ($this->MarkerColour) {
				$markerParam[] = 'color:'.$this->MarkerColour;
			}
			
			if ($this->MarkerLabel) {
				$markerParam[] = 'label:'.$this->MarkerLabel;
			}
				
			$markerParam[] = $this->MarkerLat.','.$this->MarkerLng;

			$params[] = 'markers=' . implode('%7C', $markerParam);
		}

		$params[] = 'sensor=false';

		return $output . implode('&', $params);
	}


	/**
	 * Returns a link to this location on Google Maps based off the stored address.  Returns false if there is no address
	 * @return string|boolean
	 */
	function Link() {
		if ($this->Address != '') {
			return 'https://www.google.com/maps/preview#!q='.urlencode($this->Address);
		}
		return false;
	}


	/**
	 * Returns all the properties needed for constructing a map as a JSON object, eg for putting in data-map-settings
	 * @return json
	 */
	function toJSON() {
		$output = array();
		$properties = array_keys(Config::inst()->get($this->ClassName, 'db'));
		foreach ($properties as $property) {
			$output[$property] = $this->$property;
		}

		return Convert::array2json($output);
	}


	/**
	 * Render using the default template.
	 * @return string
	 */
	function forTemplate() {
		return $this->renderWith(array($this->ClassName, 'GoogleMap'));
	}

	/**
	 * Used in the template to include the needed scripts
	 */
	function IncludeScripts() {
		return self::include_scripts();
	}

	/**
	 * For including the scripts from PHP  (eg in controller's init)
	 * What scripts it actually includes is determined by the config, which can be set in your project's config.yml
	 *
	 * GoogleMap:
	 *   include_frontend_script: false
	 * 
	 */
	static function include_scripts() {
		if (Config::inst()->get('GoogleMap', 'include_api_script')) {
			Requirements::javascript(self::api_script_src(), 'GoogleMapsAPI');
		}
		if (Config::inst()->get('GoogleMap', 'include_frontend_script')) {
			Requirements::javascript(self::frontend_script_src(), 'MapFieldFrontend');
		}
	}

	/**
	 * Returns the URL to the script that creates the map from JSON in a data attribute (eg for including via combine_files)
	 */
	static function frontend_script_src() {
		$scriptDir = basename(dirname(dirname(__DIR__))) . '/javascript';
		return $scriptDir . '/googlemaps.js';
	}

	/**
	 * Returns the URL to the maps API script (eg for including via combine_files)
	 */
	static function api_script_src() {
		$url = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false';
		
		if (Config::inst()->get('GoogleMap', 'api_key')) {
			$url .= '&key=' . Config::inst()->get('GoogleMap', 'api_key');
		}
		return $url;
	}

}