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
	 * @param array $data Data used in query
	 */
	public static function addQuery($adapter, $query, array $data = array())
	{
		self::$_queries[] = array(
			'adapter' => get_class($adapter),
			'query' => $query,
			'data' => $data
			);
		self::$_queryCount++;
	}
}