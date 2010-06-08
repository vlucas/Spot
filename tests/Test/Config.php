<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Config extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;

	public function testAddConnection()
	{
		$cfg = new Spot_Config();
		$adapter = $cfg->addConnection('test_mysql', $GLOBALS['test_adapters']['mysql']['dsn']);
		$this->assertTrue($adapter instanceof Spot_Adapter_Mysql);
	}
}