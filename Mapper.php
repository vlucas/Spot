<?php
/**
 * Base DataMapper
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Mapper
{
	protected $_config;
	
	// Entity manager
	protected static $_entityManager;
	
	// Class Names for required classes - Here so they can be easily overridden
	protected $_collectionClass = 'Spot_Entity_Collection';
	protected $_queryClass = 'Spot_Query';
	protected $_exceptionClass = 'Spot_Exception';

	// Array of error messages and types
	protected $_errors = array();


	/**
	 *	Constructor Method
	 */
	public function __construct(Spot_Config $config)
	{
		$this->_config = $config;
		
		// Ensure required classes for minimum activity are loaded
		//spot_load_class($this->_queryClass);
		//spot_load_class($this->_collectionClass);
		spot_load_class($this->_exceptionClass);

		if (!class_exists($this->_exceptionClass)) {
			throw new Spot_Exception("The exception class of '".$this->_exceptionClass."' defined in '".get_class($this)."' does not exist.");
		}
	}


	/**
	 * Get config class mapper was instantiated with
	 *
	 * @return Spot_Config
	 */
	public function config()
	{
		return $this->_config;
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
	 * Get datasource name
	 *
	 * @param string $entityName Name of the entity class
	 * @return string Name of datasource defined on entity class
	 */
	public function datasource($entityName)
	{
		return $this->entityManager()->datasource($entityName);
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
	 * Get field information exactly how it is defined in the class
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fieldsDefined($entityName)
	{
		return $this->entityManager()->fieldsDefined($entityName);
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
	 * @param string $entity Instance of an entity to find the primary key of
	 */
	public function primaryKey($entity)
	{
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
	 * Get connection to use
	 *
	 * @param string $connectionName Named connection or entity class name
	 * @return Spot_Adapter
	 * @throws Spot_Exception
	 */
	public function connection($connectionName)
	{
		// Try getting connection based on given name
		if($connection = $this->config()->connection($connectionName)) {
			return $connection;
		} elseif($connection = $this->entityManager()->connection($connectionName)) {
			return $connection;
		} elseif($connection = $this->config()->defaultConnection()) {
			return $connection;
		}
		
		throw new Spot_Exception("Connection '" . $connectionName . "' does not exist. Please setup connection using Spot_Config::addConnection().");
	}
	
	
	/**
	 * Get array of entity data
	 */
	public function data($entity, array $data = array())
	{
		if(!is_object($entity)) {
			throw new $this->_exceptionClass("Entity must be an object, type '" . gettype($entity) . "' given");
		}
		
		// SET data
		if(count($data) > 0) {
			foreach($data as $key => $value) {
				$entity->$key = $value;
			}
			return $entity;
		}
		
		// GET data
		$data = array();
        $entityName = get_class($entity);
		$entityFieldsDefined = $this->fieldsDefined($entityName);
		foreach($entityFieldsDefined as $field => $fieldInfo) {
			$value = $entity->$field;
			
			if($value == $entityFieldsDefined[$field]) {
				$value = null;
			}
			
			// Empty values will be NULL (easier to be handled by databases)
			$data[$field] = $this->isEmpty($value) ? null : $value;
		}
		
		return $data;
	}
	

	/**
	 * Get a new entity object, or an existing
	 * entity from identifiers
	 *
	 * @param string $entityClass Name of the entity class
	 * @param mixed $identifier Primary key or array of key/values
	 * @return mixed Depends on input
	 * 			false If $identifier is scalar and no entity exists
	 */
	public function get($entityClass, $identifier = false)
	{
		if(false === $identifier) {
			// No parameter passed, create a new empty entity object
			$entity = new $entityClass();
            $this->data($entity, array($this->primaryKeyField($entityClass) => null));
		} else if(is_array($identifier)) {
			// An array was passed, create a new entity with that data
			$entity = new $entityClass($identifier);
            $this->data($entity, array($this->primaryKeyField($entityClass) => null));
		} else {
			// Scalar, find record by primary key
			$entity = $this->first($entityClass, array($this->primaryKeyField($entityClass) => $identifier));
			if(!$entity) {
				return false;
			}
            $this->loadRelations($entity);
		}

		// Set default values and return entity object
        $entityDefaultValues = $this->entityManager()->fieldDefaultValues($entityClass);
        if(count($entityDefaultValues) > 0) {
            $this->data($entity, $entityDefaultValues);
        }
        
		return $entity;
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
		$query = new $this->_queryClass($this, $entityName);
		$query->select($fields, $this->datasource($entityName));
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
		if(!is_object($entity)) {
			throw new $this->_exceptionClass(__CLASS__ . "::" . __FUNCTION__ . " Requires an entity object as the first parameter");
		}

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
		if(is_object($entity)) {
			$entityName = get_class($entity);
			$data = $this->data($entity);
		} elseif(is_string($entity)) {
			$entityName = $entity;
			$entity = $this->get($entityName);
			$data = $options;
		} else {
			throw new $this->_exceptionClass(__CLASS__ . "::" . __FUNCTION__ . " Accepts either an entity object or entity name + data array");
		}

		// Ensure there is actually data to update
		if(count($data) > 0) {
			$result = $this->connection($entityName)->create($this->datasource($entityName), $data);

			// Update primary key on row
			$pkField = $this->primaryKeyField($entityName);
			$entity->$pkField = $result;
			
			// Load relations on new entity
			$this->loadRelations($entity);
		} else {
			$result = false;
		}

		return $result;
	}


	/**
	 * Update given entity object
     *
     * @param object $entity Entity object
     * @params array $options Array of adapter-specific options
	 */
	public function update($entity, array $options = array())
	{
        if(is_object($entity)) {
			$entityName = get_class($entity);
			$data = $this->data($entity);
		} else {
			throw new $this->_exceptionClass(__CLASS__ . "::" . __FUNCTION__ . " Requires an entity object as the first parameter");
		}

		// Handle with adapter
		if(count($data) > 0) {
			$result = $this->connection($entityName)->update($this->datasource($entityName), $data, array($this->primaryKeyField($entityName) => $this->primaryKey($entity)));
		} else {
			$result = true;
		}

		return $result;
	}


	/**
	 * Delete items matching given conditions
	 *
     * @param mixed $entityName Name of the entity class or entity object
	 * @param array $conditions Optional array of conditions in column => value pairs
     * @params array $options Optional array of adapter-specific options
	 */
	public function delete($entityName, array $conditions = array(), array $options = array())
	{
		if(is_object($entityName)) {
            $entity = $entityName;
            $entityName = get_class($entityName);
			$conditions = array(
				0 => array('conditions' => array($this->primaryKeyField($entityName) => $this->primaryKey($entity)))
				);
            // @todo Clear entity from identity map on delete, when implemented
		}

		if(is_array($conditions)) {
			return $this->connection($entityName)->delete($this->datasource($entityName), $conditions, $options);
		} else {
			throw new $this->_exceptionClass(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
		}
	}


	/**
	 * Truncate data source
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function truncateDatasource($entityName) {
		return $this->connection($entityName)->truncateDatasource($this->datasource($entityName));
	}


	/**
	 * Drop/delete data source
	 * Destructive and dangerous - drops entire data source and all data
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function dropDatasource($entityName) {
		return $this->connection($entityName)->dropDatasource($this->datasource($entityName));
	}
    
    
    /**
	 * Migrate table structure changes from model to database
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function migrate($entityName)
	{
		return $this->connection($entityName)->migrate($this->datasource($entityName), $this->fields($entityName));
	}
    
    
    /**
     * Load defined relations
     */
    public function loadRelations($entity)
    {
        $entityName = get_class($entity);
        $relations = array();
        $rels = $this->entityRelationConditions($entity);
        if(count($rels) > 0) {
            foreach($rels as $field => $relation) {
                $relationEntity = isset($relation['entity']) ? $relation['entity'] : false;
                if(!$relationEntity) {
                    throw new $this->_exceptionClass("Entity for '" . $field . "' relation has not been defined.");
                }

                // Self-referencing entity relationship?
                if($relationEntity == ':self') {
                    $relationEntity = $entityName;
                }

                // Load relation class to lazy-loading relations on demand
                $relationClass = 'Spot_Relation_' . $relation['relation'];
                if($loadedRel = spot_load_class($relationClass)) {
                    // Set field equal to relation class instance
                    $relationObj = new $relationClass($this, $relationEntity, $relation);
                    $relations[$field] = $relationObj;
                    $entity->$field = $relationObj;
                }

            }
        }
        return $relations;
    }
    
    
    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     */
    public function entityRelationConditions($entity)
    {
        $entityName = get_class($entity);
        $rels = $this->relations($entityName);
        if(count($rels) > 0) {
            foreach($rels as $field => $relation) {
                // Load foreign keys with data from current row
                // Replace ':entity.[col]' with the field value from the passed entity object
                if(isset($relation['where'])) {
                    foreach($relation['where'] as $relationCol => $col) {
                        if(is_string($col) && strpos($col, ':entity.') !== false) {
                            $col = str_replace(':entity.', '', $col);
                            $rels[$field]['where'][$relationCol] = $entity->$col;
                        }
                    }
                }
            }
        }
        return $rels;
    }
    
    
    /**
	 * Run set validation rules on fields
	 *
	 * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
	 */
	public function validate($entity)
	{
        $entityName = get_class($entity);
        
		// Check validation rules on each feild
		foreach($this->fields($entityName) as $field => $fieldAttrs) {
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
