<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */

// Require PHPUnit
require_once 'PHPUnit/Framework.php';

// Date setup
date_default_timezone_set('America/Chicago');


// Available adapters for testing
$test_adapters = array(
	'mysql' => array(
		'adapter' => 'Spot_Adapter_Mysql',
		'dsn' => 'mysql://root@localhost/test'
	),
	'mongodb' => array(
		'adapter' => 'Spot_Adapter_Mongodb',
		'dsn' => 'localhost:28017',
		'options' => array(
            'cursor' => array(
                'timeout' => 10
            ),
            'mapper' => array(
                'translate_id' => true
            )
        )
	)
);


/**
 * Return database adapter for use
 * Really hate to have to do it this way... Those PHPUnit TestSuites should be far easier than they are...
 */
$fixture_adapter = array();
function fixture_adapter()
{
	global $fixture_adapter, $testAdapters, $adapterType; // Yikes, I know...

	if(!isset($testAdapters[$adapterType])) {
		throw new Exception("[ERROR] Unknown datasource adapter type '" . $adapterType . "'");
	}

	$adapter = $testAdapters[$adapterType];

	// New adapter instance (connection) if one does not exist yet
	if(!isset($fixture_adapter[$adapterType])) {
		$adapterClass = $adapter['adapter'];
        $options = isset($adapter['options']) ? $adapter['options'] : array();
		$fixture_adapter[$adapterType] = new $adapterClass($adapter['host'], $adapter['database'], $adapter['username'], $adapter['password'], $options);
	}
	return $fixture_adapter[$adapterType];
}


/**
 * Autoload test fixtures
 */
function spot_test_autoloader($className) {
	// Don't attempt to autoload PHPUnit classes
	if(strpos($className, 'PHPUnit') !== false) {
		return false;
	}
	$classFile = str_replace('_', '/', $className) . '.php';
	require dirname(__FILE__) . '/' . $classFile;
}
spl_autoload_register('spot_test_autoloader');