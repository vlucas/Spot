<?php
/**
* @package Spot
*/

// Require Spot_Config
require_once dirname(__DIR__) . '/lib/Spot/Config.php';

// Date setup
date_default_timezone_set('America/Chicago');

// Setup available adapters for testing

$cfg = new \Spot\Config();
          
if ($_ENV['db_type'] == "mysql") {
    // MySQL
    $cfg->addConnection('test', $_ENV['db_dsn']);
}
elseif ($GLOBALS['db_type'] == "mongo")
{
    // MongoDB with adapter options
    $cfg->addConnection('test_mongodb', $_ENV['db_dsn'], array(
        'cursor' => array(
            'timeout' => 10
        ),
        'mapper' => array(
            'translate_id' => true
        )
    ));
}
else
{
    // Db Type hasn't been configured
    exit(1);
}

/**
* Return Spot mapper for use
*/
$mapper = new \Spot\Mapper($cfg);
function test_spot_mapper() {
    global $mapper;
    return $mapper;
}


/**
* Autoload test fixtures
*/
function test_spot_autoloader($className) {
    // Only autoload classes that start with "Test_" and "Entity_"
    if(false === strpos($className, 'Test_') && false === strpos($className, 'Entity_')) {
        return false;
    }
    $classFile = str_replace('_', '/', $className) . '.php';
    require __DIR__ . '/' . $classFile;
}
spl_autoload_register('test_spot_autoloader');
