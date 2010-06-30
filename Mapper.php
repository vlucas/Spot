<?php
/**
 * Base DataMapper
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Mapper
{
	// Class Names for required classes - Here so they can be easily overridden
	protected $_collectionClass = 'Spot_Entity_Collection';
	protected $_queryClass = 'Spot_Query';
	protected $_exceptionClass = 'Spot_Exception';

	// Array of error messages and types
	protected $_errors = array();
	
	// Entity manager
	protected static $_entityManager;


	/**
	 *	Constructor Method
	 */
	public function __construct($entityClass)
	{
		// Ensure required classes for minimum activity are loaded
		//spot_load_class($this->_queryClass);
		//spot_load_class($this->_collectionClass);
		spot_load_class($this->_exceptionClass);

		if (!class_exists($this->_exceptionClass)) {
			throw new Spot_Exception("The exception class of '".$this->_exceptionClass."' defined in '".get_class($this)."' does not exist.");
		}
	}


	/**
	 * Get query class name to use
	 *
	 * @return string
	 */
	public function queryClass()
	{
		return $this->_queryClass;
	}


	/**
	 * Get collection class name to use
	 *
	 * @return string
	 */
	public function collectionClass()
	{
		return $this->_collectionClass;
	}


	/**
	 * Get name of the data source
	 */
	public function datasource()
	{
		return $this->_datasource;
	}
	
	
	/**
	 * Entity manager class for storing information and meta-data about entities
	 */
	public function entityManager()
	{
		if(null === self::$_entityManager) {
			self::$_entityManager = new Spot_Entity_Manager();
		}
		return self::$_entityManager;
	}


	/**
	 * Get formatted fields with all neccesary array keys and values.
	 * Merges defaults with defined field values to ensure all options exist for each field.
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fields($entityName)
	{
		return $this->entityManager()->fields($entityName);
	}


	/**
	 * Get defined relations
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function relations($entityName)
	{
		return $this->entityManager()->relations($entityName);
	}


	/**
	 * Get value of primary key for given row result
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function primaryKey($entity)
	{
		$this->checkEntity($entity);

		$pkField = $this->entityManager()->primaryKeyField(get_class($entity));
		return $entity->$pkField;
	}


	/**
	 * Get value of primary key for given row result
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function primaryKeyField($entityName)
	{
		return $this->entityManager()->primaryKeyField($entityName);
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
	 * Get a new entity object, or an existing
	 * entity from identifiers
	 *
	 * @param mixed $identifier Primary key or array of key/values
	 * @return mixed Depends on input
	 * 			false If $identifier is scalar and no entity exists
	 */
	public function get($entityClass, $identifier = false)
	{
		if(false === $identifier) {
			// No parameter passed, create a new empty entity object
			$entity = new $entityClass();
		} else if(is_array($identifier)) {
			// An array was passed, create a new entity with that data
			$entity = new $entityClass($identifier);
		} else {
			// Scalar, find record by primary key
			$entity = $this->first($entityClass, array($this->primaryKeyField() => $identifier));
			if(!$entity) {
				return false;
			}
		}

		// Set default values and return entity object
		return $entity->data($this->_fieldDefaults);
	}

	/**
	* Checks that the entity is an instance of $this->_entityClass
	*
	* @param Spot_Entity $entity the entity to check
	* @throws Exception if the entity is not an instance of $this->_entityClass
	*/
	public function checkEntity(Spot_Entity $entity)
	{
		if (!($entity instanceof $this->_entityClass)) {
				throw new $this->_exceptionClass("Mapper expects entity of ".$this->_entityClass.", '".get_class($entity)."' given.");
		}
	}


	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param string $entityName Name of the entity class
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function all($entityName, array $conditions = array())
	{
		return $this->select($entityName)->where($conditions);
	}


	/**
	 * Find first record matching given conditions
	 *
	 * @param string $entityName Name of the entity class
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function first($entityName, array $conditions = array())
	{
		$query = $this->select($entityName)->where($conditions)->limit(1);
		$collection = $query->execute();
		if($collection) {
			return $collection->first();
		} else {
			return false;
		}
	}


	/**
	 * Begin a new database query - get query builder
	 * Acts as a kind of factory to get the current adapter's query builder object
	 *
	 * @param string $entityName Name of the entity class
	 * @param mixed $fields String for single field or array of fields
	 */
	public function select($entityName, $fields = "*")
	{
		$query = new $this->_queryClass($this);
		$query->select($entityName, $fields, $this->datasource($entityName));
		return $query;
	}


	/**
	 * Save record
	 * Will update if primary key found, insert if not
	 * Performs validation automatically before saving record
	 *
	 * @param mixed $entity Entity object or array of field => value pairs
     * @params array $options Array of adapter-specific options
	 */
	public function save($entity, array $options = array())
	{
		if(is_array($entity)) {
			$entity = $this->get()->data($entity);
		}

		$this->checkEntity($entity);

		// Run beforeSave to know whether or not we can continue
		$resultBefore = null;
		if(is_callable(array($entity, 'beforeSave'))) {
			if(false === $entity->beforeSave()) {
				return false;
			}
		}

		// Run validation
		if($this->validate($entity)) {
			$pk = $this->primaryKey($entity);
			// No primary key, insert
			if(empty($pk)) {
				$result = $this->insert($entity);
			// Has primary key, update
			} else {
				$result = $this->update($entity);
			}
		} else {
			$result = false;
		}

		// Use return value from 'afterSave' method if not null
		$resultAfter = null;
		if(is_callable(array($entity, 'afterSave'))) {
			$resultAfter = $entity->afterSave($result);
		}
		return (null !== $resultAfter) ? $resultAfter : $result;
	}


	/**
	 * Insert record
	 *
	 * @param mixed $entity Entity object or array of field => value pairs
     * @params array $options Array of adapter-specific options
	 */
	public function insert($entity, array $options = array())
	{
		if(is_array($entity)) {
			$entity = $this->get()->data($entity);
		}

		$this->checkEntity($entity);

		$data = array();
		$entityData = $entity->toArray();
		foreach($entityData as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$data[$field] = $this->isEmpty($value) ? null : $value;
			}
		}

		// Ensure there is actually data to update
		if(count($data) > 0) {
			$result = $this->adapter()->create($this->datasource(), $data);

			// Update primary key on row
			$pkField = $this->primaryKeyField();
			$entity->$pkField = $result;

			// Load relations for this row so they can be used immediately
			$relations = $this->getRelationsFor($entity);
			if($relations && is_array($relations) && count($relations) > 0) {
				foreach($relations as $relationCol => $relationObj) {
					$entity->$relationCol = $relationObj;
				}
			}
		} else {
			$result = false;
		}

		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($entity);
		}

		return $result;
	}


	/**
	 * Update given row object
     *
     * @param mixed $entity Entity object or array of field => value pairs
     * @params array $options Array of adapter-specific options
	 */
	public function update($entity, array $options = array())
	{
		$this->checkEntity($entity);

		// Ensure fields exist to prevent errors
		$binds = array();
		foreach($entity->dataModified() as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$binds[$field] = $this->isEmpty($value) ? null : $value;
			}
		}

		// Handle with adapter
		if(count($binds) > 0) {
			$result = $this->adapter()->update($this->datasource(), $binds, array($this->primaryKeyField() => $this->primaryKey($entity)));
		} else {
			$result = true;
		}

		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($entity);
		}

		return $result;
	}


	/**
	 * Delete items matching given conditions
	 *
	 * @param mixed $conditions Array of conditions in column => value pairs or Entity object
     * @params array $options Array of adapter-specific options
     *
     * @param string $entityName Name of the entity class
	 */
	public function delete($entityName, $conditions, array $options = array())
	{
		if($entityName instanceof Spot_Entity) {
			$conditions = array(
				0 => array('conditions' => array($this->primaryKeyField($entityName) => $this->primaryKey($entityName)))
				);
		}

		if(is_array($conditions)) {
			return $this->adapter()->delete($this->datasource($entityName), $conditions);
		} else {
			throw new $this->_exceptionClass(__METHOD__ . " conditions must be entity object or array, given " . gettype($conditions) . "");
		}
	}


	/**
	 * Truncate data source
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function truncateDatasource($entityName) {
		return $this->adapter()->truncateDatasource($this->datasource($entityName));
	}


	/**
	 * Drop/delete data source
	 * Destructive and dangerous - drops entire data source and all data
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function dropDatasource($entityName) {
		return $this->adapter()->dropDatasource($this->datasource($entityName));
	}


	/**
	 * Run set validation rules on fields
	 *
	 * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
	 */
	public function validate($entity)
	{
		$this->checkEntity($entity);

		// Check validation rules on each feild
		foreach($this->fields() as $field => $fieldAttrs) {
			if(isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
				// Required field
				if(empty($entity->$field)) {
					$this->error($field, "Required field '" . $field . "' was left blank");
				}
			}
		}

		// Check for errors
		if($this->hasErrors()) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * Migrate table structure changes from model to database
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function migrate($entityName)
	{
		return $this->adapter()->migrate($this->datasource($entityName), $this->fields($entityName));
	}


	/**
	 * Check if a value is empty, excluding 0 (annoying PHP issue)
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isEmpty($value)
	{
		return (empty($value) && 0 !== $value);
	}


	/**
	 * Check if any errors exist
	 *
	 * @param string $field OPTIONAL field name
	 * @return boolean
	 */
	public function hasErrors($field = null)
	{
		if(null !== $field) {
			return isset($this->_errors[$field]) ? count($this->_errors[$field]) : false;
		}
		return count($this->_errors);
	}


	/**
	 * Get array of error messages
	 *
	 * @return array
	 */
	public function errors($msgs = null)
	{
		// Return errors for given field
		if(is_string($msgs)) {
			return isset($this->_errors[$field]) ? $this->_errors[$field] : array();

		// Set error messages from given array
		} elseif(is_array($msgs)) {
			foreach($msgs as $field => $msg) {
				$this->error($field, $msg);
			}
		}
		return $this->_errors;
	}


	/**
	 * Add an error to error messages array
	 *
	 * @param string $field Field name that error message relates to
	 * @param mixed $msg Error message text - String or array of messages
	 */
	public function error($field, $msg)
	{
		if(is_array($msg)) {
			// Add array of error messages about field
			foreach($msg as $msgx) {
				$this->_errors[$field][] = $msgx;
			}
		} else {
			// Add to error array
			$this->_errors[$field][] = $msg;
		}
	}
}



/**
 * Attempt to load class file based on Spot naming conventions
 */
function spot_load_class($className)
{
	$loaded = false;

	// If class has already been defined, skip loading
	if(class_exists($className, false)) {
		$loaded = true;
	} else {
		// Require Spot_* files by assumed folder structure (naming convention)
		if(strpos($className, "Spot") !== false) {
			$classFile = str_replace("_", "/", $className);
			$loaded = require_once dirname(dirname(__FILE__)) . "/" . $classFile . ".php";
		}
	}

	return $loaded;
}
/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register('spot_load_class');