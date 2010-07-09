<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Query extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;
	
	/**
	 * Prepare the data
	 */
	public static function setUpBeforeClass()
	{
		$mapper = test_spot_mapper();
		
		$mapper->migrate('Entity_Post');
		$mapper->truncateDatasource('Entity_Post');
		
		$mapper->migrate('Entity_Post_Comment');
		$mapper->truncateDatasource('Entity_Post_Comment');

		// Insert blog dummy data
		for( $i = 1; $i <= 10; $i++ ) {
			$mapper->insert('Entity_Post', array(
				'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
				'body' => '<p>' . $i  . '_body</p>',
				'status' => $i ,
				'date_created' => $mapper->connection('Entity_Post')->dateTime()
			));
		}
	}
	
	public function testQuery()
	{
		$mapper = test_spot_mapper();
        //$post = $mapper->first(array('title' => 'Test Post'));

        //$this->assertTrue($post instanceof Spot_Entity);
	}
}