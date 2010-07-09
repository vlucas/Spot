<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Insert extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;
	
	public static function setupBeforeClass()
	{
		$mapper = test_spot_mapper();
		$mapper->migrate('Fixture_Post');
	}
	
	public function testInsertBlogPost()
	{
		$post = new Fixture_Post();
		$mapper = test_spot_mapper();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = date('Y-m-d H:i:s');
		
		$result = $mapper->insert($post); // returns inserted id
		
		$this->assertTrue($result !== false);
	}
}