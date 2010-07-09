<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Config
{
	protected $_defaultConnection;
	protected $_connections = array();
	
	
	/**
	 * Add database connection
	 *
	 * @param string $name Unique name for the connection
	 * @param string $dsn DSN string for this connection
	 * @param array $options Array of key => value options for adapter
	 * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
	 * @return Spot_Adapter_Interface Spot adapter instance
	 * @throws Spot_Exception
	 */
	public function addConnection($name, $dsn, array $options = array(), $default = false)
	{
		// Connection name must be unique
		if(isset($this->_connections[$name])) {
			throw new Spot_Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
		}
		
		$dsnp = Spot_Adapter_Abstract::parseDSN($dsn);
		$adapterClass = "Spot_Adapter_" . ucfirst($dsnp['adapter']);
		$adapter = new $adapterClass($dsn, $options);
		
		// Set as default connection?
		if(true === $default || null === $this->_defaultConnection) {
			$this->_defaultConnection = $name;
		}
		
		// Store connection and return adapter instance
		$this->_connections[$name] = $adapter;
		return $adapter;
	}
	
	
	/**
	 * Get connection by name
	 *
	 * @param string $name Unique name of the connection to be returned
	 * @return Spot_Adapter_Interface Spot adapter instance
	 * @throws Spot_Exception
	 */
	public function connection($name = null)
	{
		if(null === $name) {
			return $this->defaultConnection();
		}
		
		// Connection name must be unique
		if(!isset($this->_connections[$name])) {
			return false;
		}
		
		return $this->_connections[$name];
	}
	
	
	/**
	 * Get default connection
	 *
	 * @return Spot_Adapter_Interface Spot adapter instance
	 * @throws Spot_Exception
	 */
	public function defaultConnection()
	{
		return $this->_connections[$this->_defaultConnection];
	}
	
	
	/**
	 * Class loader
	 *
	 * @param string $className Name of class to load
	 */
	public static function loadClass($className)
	{
		$loaded = false;
	
		// If class has already been defined, skip loading
		if(class_exists($className, false)) {
			$loaded = true;
		} else {
			// Require Spot_* files by assumed folder structure (naming convention)
			if(false !== strpos($className, "Spot")) {
				$classFile = str_replace("_", "/", str_replace('Spot_', '', $className));
				$loaded = require_once(dirname(__FILE__) . "/" . $classFile . ".php");
			}
		}
	
		return $loaded;
	}
}


/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register(array('Spot_Config', 'loadClass'));