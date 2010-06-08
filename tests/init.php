<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */

// Require PHPUnit
require_once 'PHPUnit/Framework.php';

// Require Spot_Config
require_once dirname(dirname(__FILE__)) . '/Config.php';

// Date setup
date_default_timezone_set('America/Chicago');

// Available adapters for testing
$test_adapters = array(
	'mysql' => array(
		'adapter' => 'Spot_Adapter_Mysql',
		'dsn' => 'mysql://test:password@localhost/test'
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
 * Autoload test fixtures
 */
function test_spot_autoloader($className) {
	// Don't attempt to autoload PHPUnit classes
	if(strpos($className, 'PHPUnit') !== false) {
		return false;
	}
	$classFile = str_replace('_', '/', $className) . '.php';
	require dirname(__FILE__) . '/' . $classFile;
}
spl_autoload_register('test_spot_autoloader');