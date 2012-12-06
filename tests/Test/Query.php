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
	
	public function testQueryInstance()
	{
		$mapper = test_spot_mapper();
        $posts = $mapper->all('Entity_Post', array('title' => 'even_title'));
        $this->assertTrue($posts instanceof \Spot\Query);
	}
	
	public function testQueryCollectionInstance()
	{
		$mapper = test_spot_mapper();
        $posts = $mapper->all('Entity_Post', array('title' => 'even_title'));
        $this->assertTrue($posts instanceof \Spot\Query);
		$this->assertTrue($posts->execute() instanceof \Spot\Entity\Collection);
	}
	
	public function testOperatorNone()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->first('Entity_Post', array('status' => 2));
		$this->assertEquals(2, $post->status);
	}

	// Equals
	public function testOperatorEq()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->first('Entity_Post', array('status =' => 2));
		$this->assertEquals(2, $post->status);
		$post = $mapper->first('Entity_Post', array('status :eq' => 2));
		$this->assertEquals(2, $post->status);
	}
	
	// Less than
	public function testOperatorLt()
	{
		$mapper = test_spot_mapper();
		$this->assertEquals(4, $mapper->all('Entity_Post', array('status <' => 5))->count());
		$this->assertEquals(4, $mapper->all('Entity_Post', array('status :lt' => 5))->count());
	}

	// Greater than
	public function testOperatorGt()
	{
		$mapper = test_spot_mapper();
		$this->assertFalse($mapper->first('Entity_Post', array('status >' => 10)));
		$this->assertFalse($mapper->first('Entity_Post', array('status :gt' => 10)));
	}

	// Greater than or equal to
	public function testOperatorGte()
	{
		$mapper = test_spot_mapper();
		$this->assertEquals(6, $mapper->all('Entity_Post', array('status >=' => 5))->count());
		$this->assertEquals(6, $mapper->all('Entity_Post', array('status :gte' => 5))->count());
	}
	
	// Use same column name more than once
	public function testFieldMultipleUsage()
	{
		$mapper = test_spot_mapper();
		$countResult = $mapper->all('Entity_Post', array('status' => 1))->orWhere(array('status' => 2))->count();
		$this->assertEquals(2, $countResult);
	}
	
	public function testArrayDefaultIn()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->first('Entity_Post', array('status' => array(2)));
		$this->assertEquals(2, $post->status);
	}

	public function testArrayInSingle()
	{
		$mapper = test_spot_mapper();

		// Numeric
		$post = $mapper->first('Entity_Post', array('status :in' => array(2)));
		$this->assertEquals(2, $post->status);

		// Alpha
		$post = $mapper->first('Entity_Post', array('status :in' => array('a')));
		$this->assertFalse($post);
	}

	public function testArrayNotInSingle()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->first('Entity_Post', array('status !=' => array(2)));
		$this->assertFalse($post->status == 2);
		$post = $mapper->first('Entity_Post', array('status :not' => array(2)));
		$this->assertFalse($post->status == 2);
	}

	public function testArrayMultiple()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post', array('status' => array(3,4,5)));
		$this->assertEquals(3, $posts->count());
		$posts = $mapper->all('Entity_Post', array('status :in' => array(3,4,5)));
		$this->assertEquals(3, $posts->count());
	}

	public function testArrayNotInMultiple()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post', array('status !=' => array(3,4,5)));
		$this->assertEquals(7, $posts->count());
		$posts = $mapper->all('Entity_Post', array('status :not' => array(3,4,5)));
		$this->assertEquals(7, $posts->count());
	}

	public function testQueryHavingClause()
	{
		$mapper = test_spot_mapper();
    $posts = $mapper->all('Entity_Post')
        ->select('id, MAX(status) as maximus')
        ->having(array('maximus' => 10));
		$this->assertEquals(1, count($posts->toArray()));
	}

	public function testQueryCountIsCachedForSameQueryResult()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$this->assertEquals(10, $posts->count());

		// Count # of queries
		$count1 = \Spot\Log::queryCount();

		$this->assertEquals(10, $posts->count());

		// Count again to ensure it is cached with no query changes
		$count2 = \Spot\Log::queryCount();
		$this->assertEquals($count1, $count2);
	}

	public function testQueryCountIsNotCachedForDifferentQueryResult()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$this->assertEquals(10, $posts->count());

		// Count # of queries
		$count1 = \Spot\Log::queryCount();

		// Change query so count will NOT be cached
		$this->assertEquals(3, $posts->where(array('status' => array(3,4,5)))->count());

		// Count again to ensure it is NOT cached since there are query changes
		$count2 = \Spot\Log::queryCount();
		$this->assertNotEquals($count1, $count2);
	}

	public function testQueryPagerExtension()
	{
		$mapper = test_spot_mapper();
    \Spot\Query::addMethod('page', function(\Spot\Query $query, $limit, $perPage = 20) {
        return $query->limit($limit, $perPage);
    });
		$posts = $mapper->all('Entity_Post')->page(1, 1);
    // Do this instead of $posts->count() because it drops LIMIT clause to count the full dataset
    $postCount = count($posts->toArray());
		$this->assertEquals(1, $postCount);
	}
	
	public function testQueryManualReset()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$this->assertEquals(10, $posts->count());
		
		$posts->where(array('title' => 'odd_title'));
		$this->assertEquals(5, $posts->count());
		
		// We assert twice to verify that reset wasn't called internally
		// where it shouldn't have
		$this->assertNotEquals(10, $posts->count());
		
		$posts->reset();
		
		$this->assertEquals($posts->conditions, array());
		$this->assertEquals(10, $posts->count());
	}
	
  public function testQueryAutomaticReset()
  {
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$this->assertEquals(10, $posts->count());
		
		$posts->where(array('title' => 'odd_title'));
		$this->assertNotEquals(10, $posts->count());
		foreach ($posts as $post) {}
		
		$this->assertEquals($posts->conditions, array());
		$this->assertEquals(10, $posts->count());
	}
	
	public function testQuerySnapshot()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post', array('title' => 'odd_title'));
		
		$this->assertEquals(5, $posts->count());
		$posts->snapshot();
		
		$this->assertEquals(5, $posts->count());
		$posts->reset();
		
		$this->assertEquals(5, $posts->count());
		$posts->reset($hard = true);
		
		$this->assertEquals(10, $posts->count());
	}
	
	
	public function testMap()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$mapped_array = $posts->map(function($p){
			return $p->status;
		});
		
		sort($mapped_array);
		
		$this->assertEquals(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), $mapped_array);
	}
	
	public function testFilter()
	{
		$mapper = test_spot_mapper();
		$posts = $mapper->all('Entity_Post');
		$this->assertNotEquals(1, $posts->count());
		
		$filtered_array = $posts->filter(function($p){
			return $p->title == 'odd_title';
		});
		
		$this->assertEquals(5, count($filtered_array));
		
	}
}
