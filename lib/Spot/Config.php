<?php
namespace Spot;

/**
 * @package Spot
 */
class Config implements \Serializable
{
    protected $_defaultConnection;
    protected $_connections = array();
    protected static $_typeHandlers = array();

    public function __construct()
    {
        // Setup default type hanlders
        self::typeHandler('string', '\Spot\Type\String');
        self::typeHandler('text', '\Spot\Type\String');

        self::typeHandler('int', '\Spot\Type\Integer');
        self::typeHandler('integer', '\Spot\Type\Integer');

        self::typeHandler('float', '\Spot\Type\Float');
        self::typeHandler('double', '\Spot\Type\Float');
        self::typeHandler('decimal', '\Spot\Type\Float');

        self::typeHandler('bool', '\Spot\Type\Boolean');
        self::typeHandler('boolean', '\Spot\Type\Boolean');

        self::typeHandler('datetime', '\Spot\Type\Datetime');
        self::typeHandler('date', '\Spot\Type\Datetime');
        self::typeHandler('timestamp', '\Spot\Type\Integer');
        self::typeHandler('year', '\Spot\Type\Integer');
        self::typeHandler('month', '\Spot\Type\Integer');
        self::typeHandler('day', '\Spot\Type\Integer');

        self::typeHandler('serialized', '\Spot\Type\Serialized');
    }

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
            throw new Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
        }

        $dsnp = \Spot\Adapter\AdapterAbstract::parseDSN($dsn);
        $adapterClass = "\\Spot\\Adapter\\" . ucfirst($dsnp['adapter']);
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
     * Get type handler class by type
     *
     * @param string $type Field type (i.e. 'string' or 'int', etc.)
     * @return Spot_Adapter_Interface Spot adapter instance
     */
    public static function typeHandler($type, $class = null)
    {
        if(null === $class) {
            if(!isset(self::$_typeHandlers[$type])) {
                throw new \InvalidArgumentException("Type '$type' not registered. Register the type class handler with \Spot\Config::typeHanlder('$type', '\Namespaced\Path\Class').");
            }
            return self::$_typeHandlers[$type];
        }

        if(!class_exists($class)) {
            throw new \InvalidArgumentException("Second parameter must be valid className with full namespace. Check the className and ensure the class is loaded before registering it as a type handler.");
        }

        return self::$_typeHandlers[$type] = $class;
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

        // Require Spot namespaced files by assumed folder structure (naming convention)
        if(false !== strpos($className, "Spot\\")) {
            $classFile = trim(str_replace("\\", "/", str_replace("_", "/", str_replace('Spot\\', '', $className))), '\\');
            $loaded = require_once(__DIR__ . "/" . $classFile . ".php");
        }

        return $loaded;
    }


    /**
     * Default serialization behavior is to not attempt to serialize stored
     * adapter connections at all (thanks @TheSavior re: Issue #7)
     */
    public function serialize()
    {
        return serialize(array());
    }

    public function unserialize($serialized)
    {
    }
}


/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register(array('\Spot\Config', 'loadClass'));
