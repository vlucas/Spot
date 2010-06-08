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

	// Query log
	protected static $_queryLog = array();


	/**
	 *	Constructor Method
	 */
	public function __construct($entityClass)
	{
		// Ensure required classes for minimum activity are loaded
		spot_load_class($this->_queryClass);
		spot_load_class($this->_collectionClass);
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
	 * Get formatted fields with all neccesary array keys and values.
	 * Merges defaults with defined field values to ensure all options exist for each field.
	 *
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fields()
	{
		if($this->_fields) {
			$returnFields = $this->_fields;
		} else {
			$getFields = create_function('$obj', 'return get_object_vars($obj);');
			$fields = $getFields($this);

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
			foreach($fields as $fieldName => $fieldOpts) {
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
					$this->_primaryKey = $fieldName;
				}
				// Store default value
				if(null !== $fieldOpts['default']) {
					$this->_fieldDefaults[$fieldName] = $fieldOpts['default'];
				}
				// Store relations (and remove them from the mix of regular fields)
				if($fieldOpts['type'] == 'relation') {
					$this->_relations[$fieldName] = $fieldOpts;
					continue; // skip, not a field
				}

				$returnFields[$fieldName] = $fieldOpts;
			}
			$this->_fields = $returnFields;
		}
		return $returnFields;
	}


	/**
	 * Get defined relations
	 */
	public function relations()
	{
		if(!$this->_relations) {
			$this->fields();
		}
		return $this->_relations;
	}


	/**
	 * Get value of primary key for given row result
	 */
	public function primaryKey($entity)
	{
		/*
		We have to check that the entity is one that we expect,
		otherwise we might not know what primary key it has
		*/
		$this->checkEntity($entity);

		$pkField = $this->primaryKeyField();
		return $entity->$pkField;
	}


	/**
	 * Get value of primary key for given row result
	 */
	public function primaryKeyField()
	{
		return $this->_primaryKey;
	}


	/**
	 * Check if field exists in defined fields
	 */
	public function fieldExists($field)
	{
		return array_key_exists($field, $this->fields());
	}


	/**
	 * Return field type
	 *
	 * @param string $field Field name
	 * @return mixed Field type string or boolean false
	 */
	public function fieldType($field)
	{
		return $this->fieldExists($field) ? $this->_fields[$field]['type'] : false;
	}


	/**
	 * Get a new entity object, or an existing
	 * entity from identifiers
	 *
	 * @param mixed $identifier If $identifier is a Spot_Entity object, then no
	 * 								action is done, and the entity is returned
	 * 							If $identifier is an array, then a new entity is
	 * 								created with that data in key=>value form
	 * 							If $identifier is a scalar, then an entity is found
	 * 								that has that value in it's primary key field
	 * 							If $identifier is not specified then a new, empty
	 * 								Spot_Entity object is returned
	 * @throws Spot_Exception If the input is an object but not the entity type this mapper
	 * 								handles
	 *
	 * @return 	mixed Depends on input
	 * 			false If $identifier is scalar and no entity exists
	 */
	public function get($identifier = false)
	{
		if (is_object($identifier)) {
			// We will either throw an exception if it isn't a valid object
			$this->checkEntity($identifier);

			// Or return it, because it is a valid object
			return $identifier;
		}
		// Create new row object
		else if(!$identifier) {
			// No parameter passed, create a new empty entity object
			$entity = new $this->_entityClass();
		} else if(is_array($identifier)) {
			// An array was passed, create a new entity with that data
			$entity = new $this->_entityClass($identifier);
		}
		// We have a scalar, find record by primary key
		else {
			$entity = $this->first(array($this->primaryKeyField() => $identifier));
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
		if (!($entity instanceof $this->_entityClass))
				throw new $this->_exceptionClass("Mapper expects entity of ".$this->_entityClass.", '".get_class($entity)."' given.");

	}
	/**
	 * Load defined relations
	 */
	public function getRelationsFor(Spot_Entity $entity)
	{
		$this->checkEntity($entity);

		$relatedColumns = array();
		$rels = $this->getEntityRelationWithValues($entity);
		if(count($rels) > 0) {
			foreach($rels as $column => $relation) {
				$mapperName = isset($relation['mapper']) ? $relation['mapper'] : false;
				if(!$mapperName) {
					throw new $this->_exceptionClass("Relationship mapper for '" . $column . "' has not been defined.");
				}

				// Set conditions for relation query
				$relConditions = array();
				if(isset($relation['where'])) {
					$relConditions = $relation['where'];
				}

				// Is self-referencing relationship?
				if($mapperName == ':self') {
					// Currently loaded mapper
					$mapper = $this;
				} else {
					// Create new instance of mapper
					$mapper = new $mapperName($this->adapter());
				}

				// Load relation class
				$relationClass = 'Spot_Relation_' . $relation['relation'];
				if($loadedRel = spot_load_class($relationClass)) {
					// Set column equal to relation class instance
					$relationObj = new $relationClass($mapper, $relConditions, $relation);
					$relatedColumns[$column] = $relationObj;
				}

			}
		}
		return (count($relatedColumns) > 0) ? $relatedColumns : false;
	}


	/**
	 * Replace entity value placeholders on relation definitions
	 * Currently replaces ':entity.[col]' with the column value from the passed entity object
	 */
	public function getEntityRelationWithValues($entity)
	{
		$this->checkEntity($entity);

		$rels = $this->relations();
		if(count($rels) > 0) {
			foreach($rels as $column => $relation) {
				// Load foreign keys with data from current row
				// Replace ':entity.[col]' with the column value from the passed entity object
				if(isset($relation['where'])) {
					foreach($relation['where'] as $relationCol => $col) {
						if(is_string($col) && strpos($col, ':entity.') !== false) {
							$col = str_replace(':entity.', '', $col);
							$rels[$column]['where'][$relationCol] = $entity->$col;
						}
					}
				}
			}
		}
		return $rels;
	}


	/**
	 * Get result set for given PDO Statement
	 */
	public function getResultSet($stmt)
	{
		if($stmt instanceof PDOStatement) {
			$results = array();
			$resultsIdentities = array();

			// Set object to fetch results into
			$stmt->setFetchMode(PDO::FETCH_CLASS, $this->_entityClass);

			// Fetch all results into new DataMapper_Result class
			while($entity = $stmt->fetch(PDO::FETCH_CLASS)) {

				// Load relations for this row
				$relations = $this->getRelationsFor($entity);
				if($relations && is_array($relations) && count($relations) > 0) {
					foreach($relations as $relationCol => $relationObj) {
						$entity->$relationCol = $relationObj;
					}
				}

				// Store in array for ResultSet
				$results[] = $entity;

				// Store primary key of each unique record in set
				$pk = $this->primaryKey($entity);
				if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
					$resultsIdentities[] = $pk;
				}

				// Mark row as loaded
				$entity->loaded(true);
			}
			// Ensure set is closed
			$stmt->closeCursor();

			return new $this->_collectionClass($results, $resultsIdentities);

		} else {
			return array();
			//throw new $this->_exceptionClass(__METHOD__ . " expected PDOStatement object");
		}
	}


	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function all(array $conditions = array())
	{
		return $this->select()->where($conditions);
	}


	/**
	 * Find first record matching given conditions
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function first(array $conditions = array())
	{
		$query = $this->select()->where($conditions)->limit(1);
		$collection = $query->execute();
		if($collection) {
			return $collection->first();
		} else {
			return false;
		}
	}


	/**
	 * Find records with custom SQL query
	 *
	 * @param string $sql SQL query to execute
	 * @param array $binds Array of bound parameters to use as values for query
	 * @throws Spot_Exception
	 */
	public function query($sql, array $binds = array())
	{
		// Add query to log
		self::logQuery($sql, $binds);

		// Prepare and execute query
		if($stmt = $this->adapter()->prepare($sql)) {
			$results = $stmt->execute($binds);
			if($results) {
				$r = $this->getResultSet($stmt);
			} else {
				$r = false;
			}

			return $r;
		} else {
			throw new $this->_exceptionClass(__METHOD__ . " Error: Unable to execute SQL query - failed to create prepared statement from given SQL");
		}

	}


	/**
	 * Begin a new database query - get query builder
	 * Acts as a kind of factory to get the current adapter's query builder object
	 *
	 * @param mixed $fields String for single field or array of fields
	 */
	public function select($fields = "*")
	{
		$query = new $this->_queryClass($this);
		$query->select($fields, $this->datasource());
		return $query;
	}


	/**
	 * Save related rows of data
	 */
	protected function saveRelatedRowsFor($entity, array $fillData = array())
	{
		$this->checkEntity($entity);

		$relationColumns = $this->getRelationsFor($entity);
		foreach($entity->toArray() as $field => $value) {
			if($relationColumns && array_key_exists($field, $relationColumns) && (is_array($value) || is_object($value))) {
				// Determine relation object
				if($value instanceof Spot_Relation) {
					$relatedObj = $value;
				} else {
					$relatedObj = $relationColumns[$field];
				}
				$relatedMapper = $relatedObj->mapper();

				// Array of related entity objects to be saved
				if(is_array($value)) {
					foreach($value as $relatedRow) {
						// Row object
						if($relatedRow instanceof Spot_Entity) {
							$relatedRowObj = $relatedRow;

						// Associative array
						} elseif(is_array($relatedRow)) {
							$relatedRowObj = new $this->_entityClass($relatedRow);
						}

						// Set column values on row only if other data has been updated (prevents queries for unchanged existing rows)
						if(count($relatedRowObj->dataModified()) > 0) {
							$fillData = array_merge($relatedObj->foreignKeys(), $fillData);
							$relatedRowObj->data($fillData);
						}

						// Save related row
						$relatedMapper->save($relatedRowObj);
					}
				}
			}
		}
	}


	/**
	 * Called before the records saves
	 * Performs validation automatically before saving record by default
	 *
	 * @param mixed $entity Entity object
	 * @return boolean False will STOP the entity from being saved and make save() return false as well.
	 */
	public function beforeSave(Spot_Entity $entity) {}


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
		if(false === $this->beforeSave($entity)) {
			return false;
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
		$resultAfter = $this->afterSave($entity, $result);
		return (null !== $resultAfter) ? $resultAfter : $result;
	}


	/**
	 * Called after the records saves
	 *
	 * @param mixed $entity Entity object
	 * @param mixed $result Result from save() method
	 * @return mixed Any return value other than NULL (or no return statement) will be passed though to the save() result
	 */
	public function afterSave(Spot_Entity $entity, $result) {}


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
	 */
	public function delete($conditions, array $options = array())
	{
		if($conditions instanceof Spot_Entity) {
			$conditions = array(
				0 => array('conditions' => array($this->primaryKeyField() => $this->primaryKey($conditions)))
				);
		}

		if(is_array($conditions)) {
			return $this->adapter()->delete($this->datasource(), $conditions);
		} else {
			throw new $this->_exceptionClass(__METHOD__ . " conditions must be entity object or array, given " . gettype($conditions) . "");
		}
	}


	/**
	 * Truncate data source
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateDatasource() {
		return $this->adapter()->truncateDatasource($this->datasource());
	}


	/**
	 * Drop/delete data source
	 * Destructive and dangerous - drops entire data source and all data
	 */
	public function dropDatasource() {
		return $this->adapter()->dropDatasource($this->datasource());
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
	 */
	public function migrate()
	{
		return $this->adapter()->migrate($this->datasource(), $this->fields());
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


	/**
	 * Prints all executed SQL queries - useful for debugging
	 */
	public function debug($entity = null)
	{
		echo "<p>Executed " . $this->queryCount() . " queries:</p>";
		echo "<pre>\n";
		print_r(self::$_queryLog);
		echo "</pre>\n";
	}


	/**
	 * Get count of all queries that have been executed
	 *
	 * @return int
	 */
	public function queryCount()
	{
		return count(self::$_queryLog);
	}


	/**
	 * Log query
	 *
	 * @param string $sql
	 * @param array $data
	 */
	public static function logQuery($sql, $data = null)
	{
		self::$_queryLog[] = array(
			'query' => $sql,
			'data' => $data
			);
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
			$loaded = require_once(dirname(dirname(dirname(__FILE__))) . "/" . $classFile . ".php");
		}
	}

	return $loaded;
}
/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register('spot_load_class');
