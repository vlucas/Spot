<?php
/**
 * Entity Manager for storing information about entities
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Entity_Manager
{
	protected static $_fields = array();
	protected static $_fieldsDefined = array();
	protected static $_fieldDefaultValues = array();
	protected static $_relations = array();
	protected static $_primaryKeyField = array();


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
			
			// Reflection on class to get field info
			$reflect = new ReflectionClass($entityName);
			$fields = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			$entityObject = new $entityName();

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
			foreach($fields as $field) {
				$fieldName = $field->getName();
				$fieldOpts = $field->getValue($entityObject);
				
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
}