<?php
/**
 * Logging class for all query activity
 * 
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Log
{
	protected static $_queries = array();
	protected static $_queryCount = 0;
	
	
	/**
	 * Add query to log
	 *
	 * @param Spot_Adpater_Interface Instance of adapter used to generate the query
	 * @param mixed $query Query run
	 * @param mixed $data Data used in query - usually array, but can be scalar or null
	 */
	public static function addQuery($adapter, $query, $data = null)
	{
		self::$_queries[] = array(
			'adapter' => get_class($adapter),
			'query' => $query,
			'data' => $data
			);
		self::$_queryCount++;
	}
	
	
	/**
	 * Get a count of how many queries have been run
	 *
	 * @return int Total number of queries that have been run
	 */
	public static function queryCount()
	{
		return self::$_queryCount;
	}
}