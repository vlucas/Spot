<?php
/**
 * @package Spot
 */
class Test_CRUD extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_Post');
    }
    public static function tearDownAfterClass()
    {
        $mapper = test_spot_mapper();
        $mapper->truncateDatasource('Entity_Post');
    }

    public function testSampleNewsInsert()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->get('Entity_Post');
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->date_created = new \DateTime();
        $result = $mapper->insert($post); // returns an id

        $this->assertTrue($result !== false);
    }

    public function testSampleNewsInsertWithEmptyNonRequiredFields()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->get('Entity_Post');
        $post->title = "Test Post With Empty Values";
        $post->body = "<p>Test post here.</p>";
        $post->date_created = null;
        try {
            $result = $mapper->insert($post); // returns an id
        } catch(Exception $e) {
            $result = false;
        }

        $this->assertTrue($result !== false);
    }

    public function testSelect()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->first('Entity_Post', array('title' => "Test Post"));

        $this->assertTrue($post instanceof Entity_Post);
    }

    public function testInsertThenSelectReturnsProperTypes()
    {
        // Insert Post into database
        $mapper = test_spot_mapper();
        $post = $mapper->get('Entity_Post');
        $post->title = "Types Test";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->status = 1;
        $post->date_created = new \DateTime();
        $result = $mapper->insert($post); // returns an id

        // Read Post from database
        $post = $mapper->get('Entity_Post', $result);

        // Strict equality
        $this->assertSame(1, $post->status);
        $postData = $post->data();
        $this->assertSame(1, $postData['status']);
    }

    public function testSampleNewsUpdate()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->first('Entity_Post', array('title' => "Test Post"));
        $this->assertTrue($post instanceof Entity_Post);

        $post->title = "Test Post Modified";
        $result = $mapper->update($post); // returns boolean

        $postu = $mapper->first('Entity_Post', array('title' => "Test Post Modified"));
        $this->assertTrue($postu instanceof Entity_Post);
    }

    public function testSampleNewsDelete()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->first('Entity_Post', array('title' => "Test Post Modified"));
        $result = $mapper->delete($post);

        $this->assertTrue((boolean) $result);
    }

    public function testMultipleConditionDelete()
    {
        $mapper = test_spot_mapper();
        for( $i = 1; $i <= 10; $i++ ) {
            $mapper->insert('Entity_Post', array(
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => $mapper->connection('Entity_Post')->dateTime()
            ));
        }
        
        $result = $mapper->delete('Entity_Post', array('status !=' => array(3,4,5), 'title' => 'odd_title'));
        $this->assertTrue((boolean) $result);
        $this->assertEquals(3, $result);
        
    }
}
