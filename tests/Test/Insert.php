<?php
/**
 * @package Spot
 */
class Test_Insert extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_Post');
    }

    public function testInsertPostEntity()
    {
        $post = new Entity_Post();
        $mapper = test_spot_mapper();
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->date_created = $mapper->connection('Entity_Post')->dateTime();
        $post->author_id = 1;

        $result = $mapper->insert($post); // returns inserted id

        $this->assertTrue($result !== false);
    }

    public function testInsertPostArray()
    {
        $mapper = test_spot_mapper();
        $post = array(
            'title' => "Test Post",
            'author_id' => 1,
            'body' => "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>",
            'date_created' => new \DateTime()
        );
        $result = $mapper->insert('Entity_Post', $post); // returns inserted id

        $this->assertTrue($result !== false);
    }

    public function testCreateInsertsEntity()
    {
        $mapper = test_spot_mapper();
        $post = array(
            'title' => "Test Post 101",
            'author_id' => 101,
            'body' => "<p>Test Post 101</p><p>It's really quite lovely.</p>",
            'date_created' => new \DateTime()
        );
        $result = $mapper->create('Entity_Post', $post);

        $this->assertTrue($result !== false);
    }

    public function testBuildReturnsEntityUnsaved()
    {
        $mapper = test_spot_mapper();
        $post = array(
            'title' => "Test Post 100",
            'author_id' => 100,
            'body' => "<p>Test Post 100</p>",
            'date_created' => new \DateTime()
        );
        $result = $mapper->build('Entity_Post', $post);

        $this->assertInstanceOf('Entity_Post', $result);
        $this->assertTrue($result->isNew());
        $this->assertEquals(null, $result->id);
    }

    public function testCreateReturnsEntity()
    {
        $mapper = test_spot_mapper();
        $post = array(
            'title' => "Test Post 101",
            'author_id' => 101,
            'body' => "<p>Test Post 101</p>",
            'date_created' => new \DateTime()
        );
        $result = $mapper->create('Entity_Post', $post);

        $this->assertInstanceOf('Entity_Post', $result);
        $this->assertFalse($result->isNew());
    }

    public function testInsertNewEntitySavesWithIdAlreadySet()
    {
        $mapper = test_spot_mapper();
        $post = new Entity_Post(array(
            'id' => 2001,
            'title' => "Test Post 2001",
            'author_id' => 2001,
            'body' => "<p>Test Post 2001</p>"
        ));
        $result = $mapper->insert($post);
        $entity = $mapper->get('Entity_Post', $post->id);

        $this->assertInstanceOf('Entity_Post', $entity);
        $this->assertFalse($entity->isNew());
    }
}
