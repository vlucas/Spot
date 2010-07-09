<?php
/**
 * Entity Manager for storing information about entities
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Entity_Manager
{
	// Field and relation info
	protected static $_properties = array();
	protected static $_fields = array();
	protected static $_fieldsDefined = array();
	protected static $_fieldDefaultValues = array();
	protected static $_relations = array();
	protected static $_primaryKeyField = array();
	
	// Connection and datasource info
	protected static $_connection = array();
	protected static $_datasource = array();


	/**
	 * Get formatted fields with all neccesary array keys and values.
	 * Merges defaults with defined field values to ensure all options exist for each field.
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fields($entityName)
	{
		if(isset(self::$_fields[$entityName])) {
			$returnFields = self::$_fields[$entityName];
		} else {
			// New entity instance for default property reflection
			$entityObject = new $entityName();
			
			// Neat little trick to get all object properties AND their values without using reflection
			$rawEntityProperties = (array) $entityObject;
			
			// Property storage
			$entityProperties = array(
				'private' => array(),
				'protected' => array(),
				'public' => array()
				);
			
			// Assemble object properties into a neat array for convenient access
			foreach($rawEntityProperties as $propName => $propValue) {
				// Private
				if(strpos($propName, $entityName) === 1) { // Class name in front of property name
					$propName = trim(str_replace($entityName, '', $propName));
					$entityProperties['private'][$propName] = $propValue;
				}
				// Protected
				elseif(strpos($propName, '*') === 1) { // Asterisk "*" in front of property name
					$propName = trim(str_replace('*', '', $propName));
					$entityProperties['protected'][$propName] = $propValue;
				}
				// Public
				else {
					$entityProperties['public'][$propName] = $propValue;
				}
			}
			
			// Datasource info
			if(isset($entityProperties['protected']['_datasource'])) {
				self::$_datasource[$entityName] = $entityProperties['protected']['_datasource'];
			} else {
				throw new Spot_Exception("Entity must have a datasource defined. Please define a protected property named '_datasource' on your '" . $entityName . "' entity class.");
			}
			
			// Connection info
			if(isset($entityProperties['protected']['_connection'])) {
				self::$_connection[$entityName] = $entityProperties['protected']['_connection'];
			} else {
				// No adapter specified will use default one from config object (or first one set if default is not explicitly set)
				self::$_connection[$entityName] = false;
			}
			
			// Default settings for all fields
			$fieldDefaults = array(
				'type' => 'string',
				'default' => null,
				'length' => null,
				'required' => false,
				'null' => true,
				'unsigned' => false,

				'primary' => false,
				'index' => false,
				'unique' => false,
				'serial' => false,

				'relation' => false
				);

			// Type default overrides for specific field types
			$fieldTypeDefaults = array(
				'string' => array(
					'length' => 255
					),
				'float' => array(
					'length' => array(10,2)
					),
				'int' => array(
					'length' => 10,
					'unsigned' => true
					)
				);

			$returnFields = array();
			foreach($entityProperties['public'] as $fieldName => $fieldOpts) {
				// Store field definition exactly how it is defined before modifying it below
				if($fieldOpts['type'] != 'relation') {
					self::$_fieldsDefined[$entityName][$fieldName] = $fieldOpts;
				}
				
				// Format field will full set of default options
				if(isset($fieldInfo['type']) && isset($fieldTypeDefaults[$fieldOpts['type']])) {
					// Include type defaults
					$fieldOpts = array_merge($fieldDefaults, $fieldTypeDefaults[$fieldOpts['type']], $fieldOpts);
				} else {
					// Merge with defaults
					$fieldOpts = array_merge($fieldDefaults, $fieldOpts);
				}

				// Store primary key
				if(true === $fieldOpts['primary']) {
					self::$_primaryKeyField[$entityName] = $fieldName;
				}
				// Store default value
				if(null !== $fieldOpts['default']) {
					self::$_fieldDefaultValues[$entityName][$fieldName] = $fieldOpts['default'];
				}
				// Store relations (and remove them from the mix of regular fields)
				if($fieldOpts['type'] == 'relation') {
					self::$_relations[$entityName][$fieldName] = $fieldOpts;
					continue; // skip, not a field
				}

				$returnFields[$fieldName] = $fieldOpts;
			}
			self::$_fields[$entityName] = $returnFields;
		}
		return $returnFields;
	}
	
	
	/**
	 * Get field information exactly how it is defined in the class
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Array of field key => value pairs
	 */
	public function fieldsDefined($entityName)
	{
		if(!isset(self::$_fieldsDefined[$entityName])) {
			$this->fields($entityName);
		}
		return self::$_fieldsDefined[$entityName];
	}
	
	
	/**
	 * Get field default values as defined in class field definitons
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Array of field key => value pairs
	 */
	public function fieldDefaultValues($entityName)
	{
		if(!isset(self::$_fieldDefaultValues[$entityName])) {
			$this->fields($entityName);
		}
		return self::$_fieldDefaultValues[$entityName];
	}


	/**
	 * Get defined relations
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function relations($entityName)
	{
		if(!isset(self::$_relations[$entityName])) {
			$this->fields($entityName);
		}
		return self::$_relations[$entityName];
	}


	/**
	 * Get value of primary key for given row result
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function primaryKeyField($entityName)
	{
		if(!isset(self::$_primaryKeyField[$entityName])) {
			$this->fields($entityName);
		}
		return self::$_primaryKeyField[$entityName];
	}


	/**
	 * Check if field exists in defined fields
	 *
	 * @param string $entityName Name of the entity class
	 * @param string $field Field name to check for existence
	 */
	public function fieldExists($entityName, $field)
	{
		return array_key_exists($field, $this->fields($entityName));
	}


	/**
	 * Return field type
	 *
	 * @param string $entityName Name of the entity class
	 * @param string $field Field name
	 * @return mixed Field type string or boolean false
	 */
	public function fieldType($entityName, $field)
	{
		$fields = $this->fields($entityName);
		return $this->fieldExists($entityName, $field) ? $fields[$field]['type'] : false;
	}
	
	
	/**
	 * Get defined connection to use for entity
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function connection($entityName)
	{
		$this->fields($entityName);
		if(!isset(self::$_connection[$entityName])) {
			return false;
		}
		return self::$_connection[$entityName];
	}
	
	
	/**
	 * Get name of datasource for given entity class
	 *
	 * @param string $entityName Name of the entity class
	 * @return string
	 */
	public function datasource($entityName)
	{
		if(!isset(self::$_datasource[$entityName])) {
			$this->fields($entityName);
		}
		return self::$_datasource[$entityName];
	}
}