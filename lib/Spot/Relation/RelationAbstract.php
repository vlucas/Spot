<?php
namespace Spot\Relation;
use Spot\Entity;

/**
 * Abstract class for relations
 * 
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class RelationAbstract
{
    protected $_mapper;
    protected $_sourceEntity;
    protected $_entityName;
    protected $_foreignKeys;
    protected $_conditions;
    protected $_relationData;
    protected $_collection;
    protected $_relationRowCount;


    /**
     * Constructor function
     *
     * @param object $mapper Spot_Mapper_Abstract object to query on for relationship data
     * @param array $resultsIdentities Array of key values for given result set primary key
     */
    public function __construct(\Spot\Mapper $mapper, $entity_or_collection, array $relationData)
    {
        $entityType = null;
        if($entity_or_collection instanceof \Spot\Entity) {
            $entityType = get_class($entity_or_collection);
        } else if($entity_or_collection instanceof \Spot\Entity\Collection) {
            $entityType = $entity_or_collection->entityName();
        } else {
            throw new \InvalidArgumentException("Entity or collection must be an instance of \\Spot\\Entity or \\Spot\\Entity\\Colletion");
        }

        $this->_mapper = $mapper;
        $this->_sourceEntity = $entity_or_collection;
        $this->_entityName = isset($relationData['entity']) ? $relationData['entity'] : null;
        $this->_conditions = isset($relationData['where']) ? $relationData['where'] : array();
        $this->_relationData = $relationData;

        // Checks ...
        if(null === $this->_entityName) {
            throw new \InvalidArgumentException("Relation description key 'entity' must be set to an Entity class name.");
        }
    }


    /**
     * Get source entity object
     */
    public function sourceEntity()
    {
        return $this->_sourceEntity;
    }


    /**
     * Get related entity name
     */
    public function entityName()
    {
        return ($this->_entityName == ':self') ? ($this->sourceEntity() instanceof \Spot\Entity\Collection ? $this->sourceEntity()->entityName() : get_class($this->sourceEntity())) : $this->_entityName;
    }


    /**
     * Get mapper instance
     */
    public function mapper()
    {
        return $this->_mapper;
    }


    /**
     * Get unresolved foreign key relations
     *
     * @return array
     */
    public function unresolvedConditions()
    {
        return $this->_conditions;
    }


    /**
     * Get foreign key relations
     *
     * @return array
     */
    public function conditions()
    {
        return $this->resolveEntityConditions($this->sourceEntity(), $this->_conditions);
    }


    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     */
    public function resolveEntityConditions($entity, array $conditions, $replace = ':entity.')
    {
        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        if($conditions) {
            foreach($conditions as $relationCol => $col) {
                if(is_string($col) && false !== strpos($col, $replace)) {
                    $col = str_replace($replace, '', $col);
                    if($entity instanceof \Spot\Entity) {
                        $conditions[$relationCol] = $entity->$col;
                    } else if($entity instanceof \Spot\Entity\Collection) {
                        $conditions[$relationCol] = $entity->toArray($col);
                    }
                }
            }
        }
        return $conditions;
    }


    /**
     * Get sorting for relations
     *
     * @return array
     */
    public function relationOrder()
    {
        $sorting = isset($this->_relationData['order']) ? $this->_relationData['order'] : array();
        return $sorting;
    }


    /**
     * Called automatically when attribute is printed
     */
    public function __toString()
    {
        // Load related records for current row
        $res = $this->execute();
        return ($res) ? "1" : "0";
    }



    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    abstract protected function toQuery();


    /**
     * Fetch and cache returned query object from internal toQuery() method
     */
    public function execute()
    {
        if(!$this->_collection) {
            $this->_collection = $this->toQuery();
        }
        return $this->_collection;
    }


    /**
     * Manually assign a collection to prevent execute() from firing
     */
    public function assignCollection($collection) {
        $this->_collection = $collection;
    }


    /**
     * Passthrough for missing methods on expected object result
     */
    public function __call($func, $args)
    {
        $obj = $this->execute();
        if(is_object($obj)) {
            return call_user_func_array(array($obj, $func), $args);
        } else {
            return $obj;
        }
    }
}
