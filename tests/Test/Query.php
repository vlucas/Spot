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

        foreach(array('Entity_Post', 'Entity_Post_Comment', 'Entity_Tag', 'Entity_PostTag', 'Entity_Author') as $entity) {
            $mapper->migrate($entity);
            $mapper->truncateDatasource($entity);
        }

        // Insert blog dummy data
        for( $i = 1; $i <= 3; $i++ ) {
            $tag_id = $mapper->insert('Entity_Tag', array(
                'name' => "Title {$i}"
            ));
        }
        for( $i = 1; $i <= 3; $i++ ) {
            $author_id = $mapper->insert('Entity_Author', array(
                'email' => $i.'user@somewhere.com',
                'password' => 'securepassword'
            ));
        }
        for( $i = 1; $i <= 10; $i++ ) {
            $post_id = $mapper->insert('Entity_Post', array(
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => $mapper->connection('Entity_Post')->dateTime(),
                'author_id' => rand(1,3)
            ));
            for( $j = 1; $j <= 2; $j++ ) {
                $mapper->insert('Entity_Post_Comment', array(
                    'post_id' => $post_id,
                    'name' => ($j % 2 ? 'odd' : 'even' ). '_title',
                    'email' => 'bob@somewhere.com'
                ));
            }
            for( $j = 1; $j <= $i % 3; $j++ ) {
                $posttag_id = $mapper->insert('Entity_PostTag', array(
                    'post_id' => $post_id,
                    'tag_id' => $j
                ));
            }
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

    public function testWithRelationsSyntax()
    {
        $mapper = test_spot_mapper();

        $posts = $mapper->all('Entity_Post');

        $this->assertInstanceOf('\Spot\Query', $posts->with('comments'));

        $posts->with('comments');

        $this->assertEquals($posts->with(), array('comments'));

        $posts->with(array('nothing'));

        $this->assertEquals($posts->with(), array('comments'));

        $posts->with(array('comments', 'comments'));

        $this->assertEquals($posts->with(), array('comments'));

        $posts->with(false);

        $this->assertEquals($posts->with(), array());
    }

    public function testQueryHasManyWith()
    {
        $mapper = test_spot_mapper();

        $count1 = \Spot\Log::queryCount();

        $posts = $mapper->all('Entity_Post')->with('comments');

        $found_posts = $posts->execute();

        $count2 = \Spot\Log::queryCount();

        $this->assertEquals($count1 + 2, $count2);

        $count3 = \Spot\Log::queryCount();

        foreach ($posts as $post) {
            $this->assertInstanceOf('\\Spot\\Relation\\HasMany', $post->comments);
            foreach ($post->comments as $comment) {
                $this->assertEquals($comment->post_id, $post->id);
            }
        }

        $count4 = \Spot\Log::queryCount();

        $this->assertEquals($count3 + 2, $count4);

    }

    public function testQueryHasManyThroughWith()
    {
        $mapper = test_spot_mapper();

        $count1 = \Spot\Log::queryCount();

        $posts = $mapper->all('Entity_Post')->with(array('tags'))->execute();

        $count2 = \Spot\Log::queryCount();

        // @todo: Currently 'HasManyThrough' queries take 3 DB calls
        $this->assertEquals($count1 + 4, $count2);

        $count3 = \Spot\Log::queryCount();

        $posts = $mapper->all('Entity_Post')->with(array('tags', 'comments'))->execute();

        $count4 = \Spot\Log::queryCount();

        $this->assertEquals($count3 + 5, $count4);
    }

    public function testQueryHasOneWith()
    {
        $mapper = test_spot_mapper();

        $count1 = \Spot\Log::queryCount();

        $posts = $mapper->all('Entity_Post')->with(array('author'))->execute();

        $count2 = \Spot\Log::queryCount();

        // @todo: Theoretically, 'HasOne' calls could be added as JOIN
        $this->assertEquals($count1 + 2, $count2);

        foreach ($posts as $post) {
            $this->assertEquals($post->author_id, $post->author->id);
            $this->assertInstanceOf('\Spot\Relation\HasOne', $post->author);
        }

        $count3 = \Spot\Log::queryCount();

        $this->assertEquals($count1 + 2, $count3);
    }
}
