<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Entity_Manager extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;

	public function testEntityFields()
	{
		$mapper = spot_mapper();
		$post = new Fixture_Post();
		
		$fields = $mapper->fields('Fixture_Post');
		
		// Assert $fields are correct
		$this->assertEquals(array_keys($fields), array('id', 'title', 'body', 'status', 'date_created'));
		/*
		$this->assertEquals($fields, array(
			"id" => array(
				"type" =>"int",
				"default" => null,
				"length" =>null,
				"required" => false,
				"null" => true, 
				"unsigned" => false,
				"primary" => true,
				"index" => false,
				"unique" => false,
				"serial" => true,
				"relation" => false
			),
			"title" => array(
				"type" =>"string",
				"default" => null,
				"length" =>null,
				"required" => true,
				"null" => true, 
				"unsigned" => false,
				"primary" => true,
				"index" => false,
				"unique" => false,
				"serial" => false,
				"relation" => false
			),
			"body" => array(
				"type" =>"text",
				"default" => null,
				"length" =>null,
				"required" => true,
				"null" => true, 
				"unsigned" => false,
				"primary" => true,
				"index" => false,
				"unique" => false,
				"serial" => false,
				"relation" => false
			),
			"status" => array(
				"type" =>"int",
				"default" => null,
				"length" =>null,
				"required" => true,
				"null" => true, 
				"unsigned" => false,
				"primary" => true,
				"index" => false,
				"unique" => false,
				"serial" => false,
				"relation" => false
			),
			"date_created" => array(
				"type" =>"datetime",
				"default" => null,
				"length" =>null,
				"required" => true,
				"null" => true, 
				"unsigned" => false,
				"primary" => true,
				"index" => false,
				"unique" => false,
				"serial" => false,
				"relation" => false
			)
		));
		*/
	}
	
	public function testEntityRelations()
	{
		$mapper = spot_mapper();
		$post = new Fixture_Post();
		
		$relations = $mapper->relations('Fixture_Post');
		
		// Assert $relations are correct
		$this->assertEquals(array_keys($relations), array('comments'));
	}
}