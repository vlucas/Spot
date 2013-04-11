<?php
namespace Spot\Relation;
/**
 * DataMapper class for 'has one' relations
 * 
 * @package Spot
 * @link http://spot.os.ly
 */
class HasOne extends RelationAbstract
{
    /**
     * Load query object with current relation data
     * 
     * @return Spot_Query
     */
    public $entity = null;
    
    protected function toQuery()
    {
        return $this->mapper()->all($this->entityName(), $this->conditions())->order($this->relationOrder())->limit(1);
    }
    
    public function entity()
    {
        if (!$this->entity) {
            $this->entity = $this->execute();
            if ($this->entity instanceof \Spot\Query) {
                $this->entity = $this->entity->first();
            }
        }
        return $this->entity;
    }
    
    /**
     * isset() functionality passthrough to entity
     */
    public function __isset($key)
    {
        $entity = $this->execute();
        if($entity) {
            return isset($entity->$key);
        } else {
            return false;
        }
    }
    
    
    /**
     * Getter passthrough to entity
     */
    public function __get($var)
    {
        if($this->entity()) {
            return $this->entity()->$var;
        } else {
            return null;
        }
    }
    
    
    /**
     * Setter passthrough to entity
     */
    public function __set($var, $value)
    {
        if($this->entity()) {
            $this->entity()->$var = $value;
        }
    }
}
