<?php
/**
* @package Spot
*/

error_reporting(-1);
ini_set('display_errors', 1);

$autoload = require dirname(dirname(__FILE__)) . '/vendor/autoload.php';

// Date setup
date_default_timezone_set('America/Chicago');

// Setup available adapters for testing
$cfg = new \Spot\Config();
$dbDsn  = getenv('SPOT_DB_DSN');

if (!empty($dbDsn)) {
    $cfg->addConnection('test', $dbDsn);
} else {
    die('DSN not configured');
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
$autoload->add('SpotTest', __DIR__);

