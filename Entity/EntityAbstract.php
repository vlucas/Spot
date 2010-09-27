<?php
namespace Spot\Entity;

/**
 * Entity object
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class EntityAbstract
{
    protected static $_datasource;
    protected static $_connection;
    
    
	/**
	 * Constructor - allows setting of object properties with array on construct
	 */
	public function __construct($data = null)
	{
		// Set given data
		if($data !== null) {
			$this->data($data);
		}
	}
    
    
    /**
     * Datasource getter/setter
     */
    public static function datasource($ds = null)
    {
        $class = get_called_class();
        if(null !== $ds) {
            $class::$_datasource = $ds;
            return $this;
        }
        return $class::$_datasource;
    }
    
    
    /**
     * Named connection getter/setter
     */
    public static function connection($connection = null)
    {
        $class = get_called_class();
        if(null !== $connection) {
            $class::$_connection = $connection;
            return $this;
        }
        return $class::$_connection;
    }
	
	
	/**
	 *	Sets an object or array
	 */
	public function data($data = null)
	{
		if(null !== $data) {
			if(is_object($data) || is_array($data)) {
				foreach($data as $k => $v) {
					$this->$k = $v;
				}
				return $this;
			} else {
				throw new \InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
			}
		}
	}


	/**
	 * Enable isset() for object properties
	 */
	public function __isset($key)
	{
		return ($this->$key !== null) ? true : false;
	}
	
	
	/**
	 * String representation of the class
	 */
	public function __toString()
	{
		return __CLASS__;
	}
}
