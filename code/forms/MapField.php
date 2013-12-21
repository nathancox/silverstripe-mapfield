<?php

class MapField extends FormField {
	/**
	 * The type of map object.  Can be changed for eg if you're using a subclass of GoogleMap
	 * @var string
	 */
	public $mapObjectClass = 'GoogleMap';

	public $defaults = array(
		'CenterLat' => -42.058,
		'CenterLng' => 170.173,
		'Zoom' => 4,
		'MapType' => 'roadmap',
		'Address' => ''
	);

	/**
	 * Width of the map in the field specified as array(int min width, int max width).  Can be used to match the field to the dimensions of the map of the front end.
	 * @var array of ints
	 */
	public $mapWidth = array(300, 512);

	/**
	 * Height of the map in the field specified as array(int min height, int max height).  Can be used to match the field to the dimensions of the map of the front end.
	 * @var array of ints
	 */
	public $mapHeight = array(300, 300);

	private $mapObject = null;
	private $mapData = array();

	/**
	 * Disable storing the address.  This stops the map link from working
	 * @var boolean
	 */
	public $storeAddress = true;

	// not fully implemented
	private $fieldAction = 'update';

	public $addressFieldPlaceholder = 'enter address to place marker on the map';

	/**
	 * @param string $name      The name of the field
	 * @param string $title     The field label
	 * @param GoogleMap $mapObject [description]
	 */
	public function __construct($name, $title, $mapObject = null) {
		if ($mapObject) {
			$this->setMap($mapObject);
		}
		parent::__construct($name, $title);
	}


	public function Field($properties = array()) {
		$dir = basename(dirname(dirname(__DIR__)));
		Requirements::javascript($dir . '/javascript/MapField.js');
		Requirements::css($dir . '/css/MapField.css');

		$url = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&callback=mapFieldInit';
		if (Config::inst()->get('GoogleMap', 'api_key')) {
			$url .= '&key=' . Config::inst()->get('GoogleMap', 'api_key');
		}
		Requirements::javascript($url, 'GoogleMaps');

		return parent::Field($properties);
	}



	function setValue($value, $record = null) {
		if(empty($value) && $record) {

			if(($record instanceof DataObject) && $record->hasMethod($this->getName())) {
				$data = $record->{$this->getName()}();
				if ($data instanceof $this->mapObjectClass) {
					if ($this->mapObject && $data->ID == 0) {
						$this->value = $this->mapObject->ID;
					} else {
						$this->mapObject = $data;
						$this->value = $data->ID;
						if ($data->ID == 0) {
							$this->mapObject->update($this->defaults);
						}
					}

				} else {
					user_error("MapField::setValue() passed a ".$data->ClassName, E_USER_WARNING);
				}
			} else {
				user_error("MapField::setValue() went wrong somehow", E_USER_WARNING);
			}
		} else if (is_array($value)) {
			$this->mapData = $value;
			if (isset($value['ID'])) {
				$this->mapObject = $this->getObjectByID($value['ID']);
			} else {
				$this->mapObject = $this->getObjectByID();
			}
			if (isset($value['FieldAction'])) {
				$this->fieldAction = $value['FieldAction'];
				unset($value['FieldAction']);
			}
			$this->mapObject->update($value);
			$this->value = $this->mapObject->ID;
		} else if ((int)$value > 0) {
			$this->mapObject = $this->getObjectByID($value);
			$this->value = $value;
		} else {
			$this->value = 0;
			$this->mapObject = $this->getObjectByID();
		}

		return $this;
	}

	function getObjectByID($id = 0) {
		$objectClass = $this->mapObjectClass;
		if ($this->mapObject && $this->mapObject->ID == $id) {
			return $this->mapObject;
		} else if ((int)$id > 0) {
			$mapObject = $objectClass::get()->byID($id);
			if ($mapObject) {
				return $mapObject;
			}
		}
			
		$object = $objectClass::create();
		$object->update($this->defaults);

		return $object;
	}


	public function saveInto(DataObjectInterface $record) {
		if ($this->fieldAction == 'delete') {
			$object = $this->mapObject();
			$object->delete();
			$record->{$this->Name} = 0;
		} else {
			$object = $this->mapObject;
			$fieldName = $this->Name . 'ID';
			$object->write();
			$record->$fieldName = $object->ID;
		}

		return $this;
	}

	public function getChildFields() {
		$object = $this->mapObject;

		$childFields = FieldList::create(
			$addressField = TextField::create($this->Name . '[Search]', 'Search', ($this->storeAddress ? $object->Address : '')),
			HiddenField::create($this->Name . '[ID]', 'ID', $object->ID),
			HiddenField::create($this->Name . '[Address]', 'Address',  ($this->storeAddress ? $object->Address : '')),
			HiddenField::create($this->Name . '[CenterLat]', 'CenterLat', $object->CenterLat),
			HiddenField::create($this->Name . '[CenterLng]', 'CenterLng', $object->CenterLng),
			HiddenField::create($this->Name . '[MarkerLat]', 'MarkerLat', $object->MarkerLat),
			HiddenField::create($this->Name . '[MarkerLng]', 'MarkerLng', $object->MarkerLng),
			HiddenField::create($this->Name . '[Zoom]', 'Zoom', $object->Zoom),
			HiddenField::create($this->Name . '[MapType]', 'MapType', $object->MapType),
			HiddenField::create($this->Name . '[FieldAction]', '', $this->fieldAction)
		);
		$addressField->addExtraClass('mapfield-address');
		$addressField->setAttribute('placeholder', $this->addressFieldPlaceholder);

		return $childFields;
	}

	/**
	 * Set the DataObject class to save into.  In case you want to use a subclass of GoogleMap or something
	 * @param [type] $className [description]
	 */
	function setMapObjectClass($className) {
		$this->mapObjectClass = $className;
	}

	function getMapObjectClass() {
		return $this->mapObjectClass;
	}


	/**
	 * Set the placeholder text for the address search field
	 * @param string
	 */
	function setAddressPlaceholder($text) {
		$this->addressFieldPlaceholder = $text;
	}

	function getAddressPlaceholder($text) {
		return $this->addressFieldPlaceholder;
	}

	/**
	 * Set the size of the map in the CMS.   Each dimension can be either a single number or an array of (min, max)
	 * @param int|array $width  
	 * @param int|array $height [description]
	 */
	function setMapSize($width, $height) {
		if (is_array($width)) {
			$this->mapWidth = $width;
		} else {
			$this->mapWidth = array($width, $width);
		}
		
		if (is_array($height)) {
			$this->mapHeight = $height;
		} else {
			$this->mapHeight = array($height, $height);
		}
	}

	function setMapWidth($min, $max = null) {
		if ($max) {
			$this->mapWidth = array($min, $max);
		} else {
			$this->mapWidth = array($min, $min);
		}
	}
	function getMapWidth() {
		if ($this->mapWidth[0] == $this->mapWidth[1]) {
			return $this->mapWidth[0];
		}
		return $this->mapWidth;
	}

	function setMapHeight($min, $max = null) {
		if ($max) {
			$this->mapHeight = array($min, $max);
		} else {
			$this->mapHeight = array($min, $min);
		}
	}
	function getMapHeight() {
		if ($this->mapHeight[0] == $this->mapHeight[1]) {
			return $this->mapHeight[0];
		}
		return $this->mapHeight;
	}

	/**
	 * This returns the inline CSS used to size the map
	 */
	function getMapCSS() {
		$output = '';

		if ($this->mapWidth[0] == $this->mapWidth[1]) {
			$output .= 'width:'.$this->mapWidth[0].'px;';
		} else {
			$output .= 'min-width:'.$this->mapWidth[0].'px;';
			$output .= 'max-width:'.$this->mapWidth[1].'px;';
		}

		if ($this->mapHeight[0] == $this->mapHeight[1]) {
			$output .= 'height:'.$this->mapHeight[0].'px;';
		} else {
			$output .= 'min-height:'.$this->mapHeight[0].'px;';
			$output .= 'max-height:'.$this->mapHeight[1].'px;';
		}

		return $output;
	}

	function setStoreAddress($bool) {
		$this->storeAddress = $bool;
	}


	function setDefault($key, $value) {
		$this->defaults[$key] = $value;
	}

	function setDefaults(array $array) {
		$this->defaults = $array;
	}

	/**
	 * Assign a map object to this field manually.  Essentially sets the defaults
	 * @param GoogleMap a GoogleMap object (or whatever subclass is being used)
	 */
	function setMap($object) {
		$objectClass = $this->mapObjectClass;
		if (is_a($object,  $objectClass)) {
			$this->mapObject = $object;
		} else {
			user_error("setObject() must be passed a ".$objectClass, E_USER_WARNING);
		}
	}



	function APIKey() {
		return Config::inst()->get('GoogleMap', 'api_key');
	}



}