<?php
/**
 * Entity object
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class Spot_Entity_Abstract
{
	/**
	 * Constructor function
	 */
	public function __construct($data = null)
	{
		// Set given data
		if($data !== null) {
			$this->data($data);
		}
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
				throw new InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
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