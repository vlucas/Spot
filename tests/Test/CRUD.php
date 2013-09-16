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

        // Add encrypted type
        $mapper->config()->typeHandler('encrypted', '\Test\Type\Encrypted');
        Test\Type\Encrypted::$_key = 'SOUPER-SEEKRET1!';

        foreach(array('Entity_Post', 'Entity_Post_Comment', 'Entity_Tag', 'Entity_PostTag', 'Entity_Author', 'Entity_Setting') as $entity) {
            $mapper->migrate($entity);
        }
    }

    public static function tearDownAfterClass()
    {
        $mapper = test_spot_mapper();
        foreach(array('Entity_Post', 'Entity_Post_Comment', 'Entity_Tag', 'Entity_PostTag', 'Entity_Author') as $entity) {
            $mapper->dropDatasource($entity);
        }
    }

    public function testSampleNewsInsert()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->get('Entity_Post');
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->author_id = 1;
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
        $post->author_id = 1;
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
        $post->author_id = 1;
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
                'author_id' => 1,
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => $mapper->connection('Entity_Post')->dateTime()
            ));
        }

        $result = $mapper->delete('Entity_Post', array('status !=' => array(3,4,5), 'title' => 'odd_title'));
        $this->assertTrue((boolean) $result);
        $this->assertEquals(3, $result);

    }

    public function testPostTagUpsert()
    {
        $mapper = test_spot_mapper();
        $data = array(
            'tag_id' => 2145,
            'post_id' => 1295
        );
        $where = array(
            'tag_id' => 2145
        );

        // Posttags has unique constraint on tag+post, so insert will fail the second time
        $result = $mapper->upsert('Entity_PostTag', $data, $where);
        $result2 = $mapper->upsert('Entity_PostTag', array_merge($data, array('random' => 'blah blah')), $where);
        $postTag = $mapper->first('Entity_PostTag', $where);

        $this->assertTrue((boolean) $result);
        $this->assertTrue((boolean) $result2);
        $this->assertSame('blah blah', $postTag->random);
    }

    public function testUniqueConstraintUpsert()
    {
        $mapper = test_spot_mapper();
        $data = array(
            'skey' => 'my_setting',
            'svalue' => 'abc123'
        );
        $where = array(
            'skey' => 'my_setting'
        );

        // Posttags has unique constraint on tag+post, so insert will fail the second time
        $result = $mapper->upsert('Entity_Setting', $data, $where);
        $result2 = $mapper->upsert('Entity_Setting', array_merge($data, array('svalue' => 'abcdef123456')), $where);
        $entity = $mapper->first('Entity_Setting', $where);

        $this->assertTrue((boolean) $result);
        $this->assertTrue((boolean) $result2);
        $this->assertSame('abcdef123456', $entity->svalue);
    }
}
