<?php
namespace Spot;

/**
 * Base DataMapper
 *
 * @package Spot
 */
class Mapper
{
    protected $_config;

    // Entity manager
    protected static $_entityManager;

    // Class Names for required classes - Here so they can be easily overridden
    protected $_collectionClass = '\\Spot\\Entity\\Collection';
    protected $_queryClass = '\\Spot\\Query';
    protected $_exceptionClass = '\\Spot\\Exception';

    // Array of error messages and types
    protected $_errors = array();

    // Array of hooks
    protected $_hooks = array();

    /**
     *  Constructor Method
     */
    public function __construct(Config $config)
    {
        $this->_config = $config;

        if (!class_exists($this->_exceptionClass)) {
            throw new Exception("The exception class of '".$this->_exceptionClass."' defined in '".get_class($this)."' does not exist.");
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
            self::$_entityManager = new Entity\Manager();
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
     * @param object $entity Instance of an entity to find the primary key of
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
    public function connection($connectionName = null)
    {
        // Try getting connection based on given name
        if($connectionName === null) {
            return $this->config()->defaultConnection();
        } elseif($connection = $this->config()->connection($connectionName)) {
            return $connection;
        } elseif($connection = $this->entityManager()->connection($connectionName)) {
            return $connection;
        } elseif($connection = $this->config()->defaultConnection()) {
            return $connection;
        }

        throw new Exception("Connection '" . $connectionName . "' does not exist. Please setup connection using Spot_Config::addConnection().");
    }


    /**
     * Create collection
     */
    public function collection($entityName, $cursor, $with = array())
    {
        $results = array();
        $resultsIdentities = array();

        // Ensure PDO only gives key => value pairs, not index-based fields as well
        // Raw PDOStatement objects generally only come from running raw SQL queries or other custom stuff
        if($cursor instanceof \PDOStatement) {
            $cursor->setFetchMode(\PDO::FETCH_ASSOC);
        }

        // Fetch all results into new entity class
        // @todo Move this to collection class so entities will be lazy-loaded by Collection iteration
        $entityFields = $this->fields($entityName);
        foreach($cursor as $data) {
            $data = $this->loadEntity($entityName, $data);

            $entity = new $entityName($data);
            $entity->isNew(false);

            // Load relation objects
            $this->loadRelations($entity);

            // Store in array for Collection
            $results[] = $entity;

            // Store primary key of each unique record in set
            $pk = $this->primaryKey($entity);
            if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
                $resultsIdentities[] = $pk;
            }
        }

        $collectionClass = $this->collectionClass();
        $collection = new $collectionClass($results, $resultsIdentities, $entityName);
        return $this->with($collection, $entityName, $with);
    }

    /**
     * Pre-emtively load associations for an entire collection
     */
    public function with($collection, $entityName, $with = array()) {
        $return = $this->triggerStaticHook($entityName, 'beforeWith', array($collection, $with, $this));
        if (false === $return) {
            return $collection;
        }

        foreach($with as $relationName) {
            $return = $this->triggerStaticHook($entityName, 'loadWith', array($collection, $relationName, $this));
            if (false === $return) {
                continue;
            }

            $relationObj = $this->loadRelation($collection, $relationName);

            // double execute() to make sure we get the
            // \Spot\Entity\Collection back (and not just the \Spot\Query)
            $related_entities = $relationObj->execute()->limit(null)->execute();

            // Load all entities related to the collection
            foreach ($collection as $entity) {
                $collectedEntities = array();
                $collectedIdentities = array();
                foreach ($related_entities as $related_entity) {
                    $resolvedConditions = $relationObj->resolveEntityConditions($entity, $relationObj->unresolvedConditions());

                    // @todo this is awkward, but $resolvedConditions['where'] is returned as an array
                    foreach ($resolvedConditions as $key => $value) {
                        if ($related_entity->$key == $value) {
                            $pk = $this->primaryKey($related_entity);
                            if(!in_array($pk, $collectedIdentities) && !empty($pk)) {
                                $collectedIdentities[] = $pk;
                            }
                            $collectedEntities[] = $related_entity;
                        }
                    }
                }
                if ($relationObj instanceof \Spot\Relation\HasOne) {
                    $relation_collection = array_shift($collectedEntities);
                } else {
                    $relation_collection = new \Spot\Entity\Collection(
                        $collectedEntities, $collectedIdentities, $entity->$relationName->entityName()
                    );
                }
                $entity->$relationName->assignCollection($relation_collection);
            }
        }

        $resultAfter = $this->triggerStaticHook($entityName, 'afterWith', array($collection, $with, $this));
        return $collection;
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
            return $entity->data($data);
        }

        // GET data
        return $entity->data();
    }


    /**
     * Get a new entity object, or an existing
     * entity from identifiers
     *
     * @param string $entityClass Name of the entity class
     * @param mixed $identifier Primary key or array of key/values
     * @return mixed Depends on input
     *         false If $identifier is scalar and no entity exists
     */
    public function get($entityClass, $identifier = false)
    {
        if(false === $identifier) {
            // No parameter passed, create a new empty entity object
            $entity = new $entityClass();
            $entity->data(array($this->primaryKeyField($entityClass) => null));
        } else if(is_array($identifier)) {
            // An array was passed, create a new entity with that data
            $entity = new $entityClass($identifier);
            $entity->data(array($this->primaryKeyField($entityClass) => null));
        } else {
            // Scalar, find record by primary key
            $entity = $this->first($entityClass, array($this->primaryKeyField($entityClass) => $identifier));
            if(!$entity) {
                return false;
            }
            $this->loadRelations($entity);
        }

        // Set default values if entity not loaded
        if(!$this->primaryKey($entity)) {
            $entityDefaultValues = $this->entityManager()->fieldDefaultValues($entityClass);
            if(count($entityDefaultValues) > 0) {
                $entity->data($entityDefaultValues);
            }
        }

        return $entity;
    }


    /**
     * Get a new entity object, set given data on it
     *
     * @param string $entityClass Name of the entity class
     * @param array $data array of key/values to set on new Entity instance
     * @return object Instance of $entityClass with $data set on it
     */
    public function build($entityClass, array $data)
    {
        return new $entityClass($data);
    }


    /**
     * Get a new entity object, set given data on it, and save it
     *
     * @param string $entityClass Name of the entity class
     * @param array $data array of key/values to set on new Entity instance
     * @return object Instance of $entityClass with $data set on it
     */
    public function create($entityClass, array $data)
    {
        $entity = $this->build($entityClass, $data);
        if($this->insert($entity)) {
            return $entity;
        }
        return false;
    }


    /**
     * Find records with custom query
     *
     * @param string $entityName Name of the entity class
     * @param string $sql Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     */
    public function query($entityName, $sql, array $params = array())
    {
        $result = $this->connection($entityName)->query($sql, $params);
        if($result) {
            return $this->collection($entityName, $result);
        }
        return false;
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
            throw new $this->_exceptionClass(__METHOD__ . " Requires an entity object as the first parameter");
        }

        // Run beforeSave to know whether or not we can continue
        if (false === $this->triggerInstanceHook($entity, 'beforeSave', $this)) {
            return false;
        }

        if($entity->isNew()) {
            $result = $this->insert($entity);
        } else {
            $result = $this->update($entity);
        }

        // Use return value from 'afterSave' method if not null
        $resultAfter = $this->triggerInstanceHook($entity, 'afterSave', array($this, $result));
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
        } elseif(is_string($entity)) {
            $entityName = $entity;
            $entity = $this->get($entityName)->data($options);
        } else {
            throw new $this->_exceptionClass(__METHOD__ . " Accepts either an entity object or entity name + data array");
        }

        // Run beforeInsert to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeInsert', $this)) {
            return false;
        }

        // Run validation
        if(!$this->validate($entity)) {
            return false;
        }

        // Ensure there is actually data to update
        $data = $entity->data();
        if(count($data) > 0) {
            // Save only known, defined fields
            $entityFields = $this->fields($entityName);
            $data = array_intersect_key($data, $entityFields);

            $data = $this->dumpEntity($entityName, $data);

            // Send to adapter via named connection
            $result = $this->connection($entityName)->create($this->datasource($entityName), $data);

            // Update primary key on entity object
            $pkField = $this->primaryKeyField($entityName);
            $entity->$pkField = $result;
            $entity->isNew(false);

            // Load relations on new entity
            $this->loadRelations($entity);

            // Run afterInsert
            $resultAfter = $this->triggerInstanceHook($entity, 'afterInsert', array($this, $result));
        } else {
            $result = false;
        }

        return (null !== $resultAfter) ? $resultAfter : $result;
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
        } else {
            throw new $this->_exceptionClass(__METHOD__ . " Requires an entity object as the first parameter");
        }

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeUpdate', $this)) {
            return false;
        }

        // Run validation
        if(!$this->validate($entity)) {
            return false;
        }

        // Prepare data
        $data = $entity->dataModified();
        // Save only known, defined fields
        $entityFields = $this->fields($entityName);
        $data = array_intersect_key($data, $entityFields);
        if(count($data) > 0) {
            $data = $this->dumpEntity($entityName, $data);

            $result = $this->connection($entityName)->update($this->datasource($entityName), $data, array($this->primaryKeyField($entityName) => $this->primaryKey($entity)));

            // Run afterUpdate
            $resultAfter = $this->triggerInstanceHook($entity, 'afterUpdate', array($this, $result));
        } else {
            $result = true;
        }

        return (null !== $resultAfter) ? $resultAfter : $result;
    }


    /**
     * Upsert save entity - insert or update on duplicate key. Intended to be
     * used in conjunction with fields that are marked 'unique'
     *
     * @param string $entityClass Name of the entity class
     * @param array $data array of key/values to set on new Entity instance
     * @param array $where array of keys to select record by for updating if it already exists
     * @return object Instance of $entityClass with $data set on it
     */
    public function upsert($entityClass, array $data, array $where)
    {
        $entity = new $entityClass($data);
        $result = $this->insert($entity);
        // Unique constraint produces a validation error
        if($result === false && $entity->hasErrors()) {
            $dataUpdate = array_diff_key($data, $where);
            $existingEntity = $this->first($entityClass, $where);
            if(!$existingEntity) {
                return $entity;
            }
            $existingEntity->data($dataUpdate);
            $entity = $existingEntity;
            $result = $this->update($entity);
        }
        return $entity;
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
            $conditions = array($this->primaryKeyField($entityName) => $this->primaryKey($entity));
            // @todo Clear entity from identity map on delete, when implemented

            // Run beforeDelete to know whether or not we can continue
            $resultAfter = null;
            if (false === $this->triggerInstanceHook($entity, 'beforeDelete', $this)) {
                return false;
            }


            $result = $this->connection($entityName)->delete($this->datasource($entityName), $conditions, $options);

            // Run afterDelete
            $resultAfter = $this->triggerInstanceHook($entity, 'afterDelete', array($this, $result));
            return (null !== $resultAfter) ? $resultAfter : $result;
        }

        if(is_array($conditions)) {
            $conditions = array(0 => array('conditions' => $conditions));
            return $this->connection($entityName)->delete($this->datasource($entityName), $conditions, $options);
        } else {
            throw new $this->_exceptionClass(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
        }
    }

    /**
     * Prepare data to be dumped to the data store
     */
    public function dumpEntity($entityName, $data)
    {
        $dumpedData = array();
        $fields = $entityName::fields();
        foreach($data as $field => $value) {
            $typeHandler = \Spot\Config::typeHandler($fields[$field]['type']);
            $dumpedData[$field] = $typeHandler::_dump($value);
        }
        return $dumpedData;
    }

    /**
     * Retrieve data from the data store
     */
    public function loadEntity($entityName, $data)
    {
        $loadedData = array();
        $fields = $entityName::fields();
        $entityData = array_intersect_key($data, $fields);
        foreach($data as $field => $value) {
            // Field is in the Entity definitions
            if(isset($entityData[$field])) {
                $typeHandler = \Spot\Config::typeHandler($fields[$field]['type']);
                $loadedData[$field] = $typeHandler::_load($value);
            // Extra data returned with query (like calculated valeus, etc.)
            } else {
                $loadedData[$field] = $value;
            }
        }
        return $loadedData;
    }

    /**
     * Transaction with closure
     */
    public function transaction(\Closure $work, $entityName = null)
    {
        $connection = $this->connection($entityName);

        try {
            $connection->beginTransaction();

            // Execute closure for work inside transaction
            $result = $work($this);

            // Rollback on boolean 'false' return
            if($result === false) {
                $connection->rollback();
            } else {
                $connection->commit();
            }
        } catch(\Exception $e) {
            // Rollback on uncaught exception
            $connection->rollback();

            // Re-throw exception so we don't bury it
            throw $e;
        }
    }


    /**
     * Truncate data source
     * Should delete all rows and reset serial/auto_increment keys to 0
     *
     * @param string $entityName Name of the entity class
     */
    public function truncateDatasource($entityName)
    {
        return $this->connection($entityName)->truncateDatasource($this->datasource($entityName));
    }


    /**
     * Drop/delete data source
     * Destructive and dangerous - drops entire data source and all data
     *
     * @param string $entityName Name of the entity class
     */
    public function dropDatasource($entityName)
    {
        return $this->connection($entityName)->dropDatasource($this->datasource($entityName));
    }


    /**
     * Migrate table structure changes from model to database
     *
     * @param string $entityName Name of the entity class
     */
    public function migrate($entityName)
    {
        return $this->connection($entityName)
            ->migrate(
                $this->datasource($entityName),
                $this->fields($entityName),
                $this->entityManager()->datasourceOptions($entityName)
            );
    }


    /**
     * Load defined relations
     */
    public function loadRelations($entity, $reload = false)
    {
        $entityName = $entity instanceof \Spot\Entity\Collection ? $entity->entityName() : get_class($entity);
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        $relations = array();
        $rels = $this->relations($entityName);
        if(count($rels) > 0) {
            foreach($rels as $field => $relation) {
                $relations[$field] = $this->loadRelation($entity, $field, $reload);
            }
        }
        return $relations;
    }


    /**
     * Load defined relations
     */
    public function loadRelation($entity, $name, $reload = false)
    {
        $entityName = $entity instanceof \Spot\Entity\Collection ? $entity->entityName() : get_class($entity);
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        $rels = $this->relations($entityName);
        if (isset($rels[$name])) {
            return $this->getRelationObject($entity, $name, $rels[$name]);
        }
    }


    protected function getRelationObject($entity, $field, $relation, $reload = false) {
        $entityName = $entity instanceof \Spot\Entity\Collection ? $entity->entityName() : get_class($entity);
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        if (isset($entity->$field) && !$reload) {
            return $entity->$field;
        }

        $relationEntity = isset($relation['entity']) ? $relation['entity'] : false;
        if(!$relationEntity) {
            throw new $this->_exceptionClass("Entity for '" . $field . "' relation has not been defined.");
        }

        // Self-referencing entity relationship?
        if($relationEntity == ':self') {
            $relationEntity = $entityName;
        }

        // Load relation class to lazy-loading relations on demand
        $relationClass = '\\Spot\\Relation\\' . $relation['type'];

        // Set field equal to relation class instance
        $relationObj = new $relationClass($this, $entity, $relation);
        return $entity->$field = $relationObj;
    }


    /**
     * Run set validation rules on fields
     */
    public function validate(\Spot\Entity $entity)
    {
        $entityName = get_class($entity);

        $v = new \Valitron\Validator($entity->data());

        // Check validation rules on each feild
        $uniqueWhere = array();
        foreach($this->fields($entityName) as $field => $fieldAttrs) {
            // Required field
            if(isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
                $v->rule('required', $field);
            }

            // Unique field
            if($entity->isNew() && isset($fieldAttrs['unique']) && !empty($fieldAttrs['unique'])) {
                if(is_string($fieldAttrs['unique'])) {
                    // Named group
                    $fieldKeyName = $fieldAttrs['unique'];
                    $uniqueWhere[$fieldKeyName][$field] = $entity->$field;
                } else {
                    $uniqueWhere[$field] = $entity->$field;
                }
            }

            // Field with 'options'
            if(isset($fieldAttrs['options']) && is_array($fieldAttrs['options'])) {
                $v->rule('in', $field, $fieldAttrs['options']);
            }

            // Valitron validation rules
            if(isset($fieldAttrs['validation']) && is_array($fieldAttrs['validation'])) {
                foreach($fieldAttrs['validation'] as $rule => $ruleName) {
                    $params = array();
                    if(is_string($rule)) {
                        $params = (array) $ruleName;
                        $ruleName = $rule;
                    }
                    $params = array_merge(array($ruleName, $field), $params);
                    call_user_func_array(array($v, 'rule'), $params);
                }
            }
        }

        // Unique validation
        if(!empty($uniqueWhere)) {
            foreach($uniqueWhere as $field => $value) {
                if(!is_array($value)) {
                    $value = array($field => $entity->$field);
                }
                if($this->first($entityName, $value) !== false) {
                    $entity->error($field, "" . ucwords(str_replace('_', ' ', $field)) . " '" . implode('-', $value) . "' is already taken.");
                }
            }
        }

        if(!$v->validate()) {
            $entity->errors($v->errors(), false);
        }

        // Return error result
        return !$entity->hasErrors();
    }

    /**
     * Add event listener
     */
    public function on($entityName, $hook, $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(__METHOD__ . " for {$entityName}->{$hook} requires a valid callable, given " . gettype($callable) . "");
        }
        $this->_hooks[$entityName][$hook][] = $callable;
        return $this;
    }

    /**
     * Remove event listener
     */
    public function off($entityName, $hooks, $callable = null)
    {
        if (isset($this->_hooks[$entityName])) {
            foreach ((array)$hooks as $hook) {
                if (true === $hook) {
                    unset($this->_hooks[$entityName]);
                } else if (isset($this->_hooks[$entityName][$hook])) {
                    if (null !== $callable) {
                        if ($key = array_search($this->_hooks[$entityName][$hook], $callable, true)) {
                            unset($this->_hooks[$entityName][$hook][$key]);
                        }
                    } else {
                        unset($this->_hooks[$entityName][$hook]);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Get all hooks added on a model
     */
    public function getHooks($entityName, $hook)
    {
        $hooks = array();
        if (isset($this->_hooks[$entityName]) && isset($this->_hooks[$entityName][$hook])) {
            $hooks = $this->_hooks[$entityName][$hook];
        }
        if (is_callable(array($entityName, 'hooks'))) {
            $entityHooks = $entityName::hooks();
            if (isset($entityHooks[$hook])) {
                // If you pass an object/method combination
                if (is_callable($entityHooks[$hook])) {
                    $hooks[] = $entityHooks[$hook];
                } else {
                    $hooks = array_merge($hooks, $entityHooks[$hook]);
                }
            }
        }
        return $hooks;
    }

    /**
     * Trigger an instance hook on the passed object.
     */
    protected function triggerInstanceHook($object, $hook, $arguments = array())
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = array($arguments);
        }
        $ret = null;
        foreach($this->getHooks(get_class($object), $hook) as $callable) {
            if (is_callable(array($object, $callable))) {
                $ret = call_user_func_array(array($object, $callable), $arguments);
            } else {
                $args = array_merge(array($object), $arguments);
                $ret = call_user_func_array($callable, $args);
            }
            if (false === $ret) {
                return false;
            }
        }
        return $ret;
    }

    /**
     * Trigger a static hook.  These pass the $object as the first argument
     * to the hook, and expect that as the return value.
     */
    protected function triggerStaticHook($objectClass, $hook, $arguments)
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = array($arguments);
        }
        array_unshift($arguments, $objectClass);
        foreach ($this->getHooks($objectClass, $hook) as $callable) {
            $return = call_user_func_array($callable, $arguments);
            if (false === $return) {
                return false;
            }
        }
    }
}
