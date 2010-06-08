<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Entity_Manager extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;

	public function testReflectionEntityFields()
	{
		$post = new Fixture_Post();
		// $fields = Spot_Entity_Manger::entityFields('Fixture_Post');
		// Assert $fields are correct
	}
	
	public function testReflectionEntityRelations()
	{
		$post = new Fixture_Post();
		// $fields = Spot_Entity_Manger::entityRelations('Fixture_Post');
		// Assert $relations are correct
	}
}