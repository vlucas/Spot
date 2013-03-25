<?php
require_once dirname(dirname(__FILE__)) . '/init.php';
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Entity extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public function testEntitySetDataProperties()
    {
        $mapper = test_spot_mapper();
        $post = new Entity_Post();

        // Set data
        $post->title = "My Awesome Post";
        $post->body = "<p>Body</p>";
        $post->author_id = 1;

        $data = $post->data();
        ksort($data);

        $testData = array(
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => null,
            'data' => null,
            'author_id' => 1
            );
        ksort($testData);

        $this->assertEquals($testData, $data);

        $this->assertNull($post->asdf);
    }

    public function testEntitySetDataConstruct()
    {
        $mapper = test_spot_mapper();
        $post = new Entity_Post(array(
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'author_id' => 1
        ));

        $data = $post->data();
        ksort($data);

        $testData = array(
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => null,
            'data' => null,
            'author_id' => 1
            );
        ksort($testData);

        $this->assertEquals($testData, $data);
    }

    public function testEntityErrors()
    {
        $post = new Entity_Post(array(
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>'
        ));
        $postErrors = array(
            'title' => array('Title cannot contain the word awesome')
        );

        // Has NO errors
        $this->assertTrue(!$post->hasErrors());

        // Set errors
        $post->errors($postErrors);

        // Has errors
        $this->assertTrue($post->hasErrors());

        // Full error array
        $this->assertEquals($postErrors, $post->errors());

        // Errors for one key only
        $this->assertEquals($postErrors['title'], $post->errors('title'));
    }

    public function testDataModified() {
        $data = array(
            'title' => 'My Awesome Post 2',
            'body' => '<p>Body 2</p>'
        );

        $testData = array(
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => null,
            'data' => null,
            'author_id' => 1
            );

        // Set initial data
        $post = new Entity_Post($testData);

        $this->assertEquals($testData, $post->dataUnmodified());

        $this->assertEquals(array(), $post->dataModified());

        $this->assertFalse($post->isModified());

        $post->data($data);

        $this->assertEquals($data, $post->dataModified());

        $this->assertTrue($post->isModified('title'));

        $this->assertFalse($post->isModified('id'));

        $this->assertNull($post->isModified('asdf'));

        $this->assertTrue($post->isModified());

        $this->assertEquals($data['title'], $post->dataModified('title'));

        $this->assertEquals($testData['title'], $post->dataUnmodified('title'));

        $this->assertNull($post->dataModified('id'));

        $this->assertNull($post->dataModified('status'));
    }


    public function testDataNulls()
    {
        $data = array(
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'author_id' => 1,
        );

        $post = new Entity_Post($data);

        $post->status = null;

        $this->assertTrue($post->isModified('status'));

        $post->status = 1;

        $this->assertTrue($post->isModified('status'));

        $post->data(array('status' => null));

        $this->assertTrue($post->isModified('status'));

        $post->title = '';

        $this->assertTrue($post->isModified('title'));

        $this->title = null;

        $this->assertTrue($post->isModified('title'));

        $this->title = 'A Post';

        $post->data(array('title' => null));

        $this->assertTrue($post->isModified('title'));
    }

    public function testSerialized()
    {
        $data = array(
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'author_id' => 1,
            'data' => array('posts' => 'are cool', 'another field' => 'to serialize')
        );

        $post = new Entity_Post($data);

        $this->assertEquals($post->data, array('posts' => 'are cool', 'another field' => 'to serialize'));

        $mapper = test_spot_mapper();

        $mapper->save($post);

        $post = $mapper->all('Entity_Post')->first();

        $this->assertEquals($post->data, array('posts' => 'are cool', 'another field' => 'to serialize'));

        $post->data = 'asdf';

        $this->assertEquals($post->data, 'asdf');

        $mapper->save($post);

        $post = $mapper->all('Entity_Post')->first();

        $this->assertEquals($post->data, 'asdf');
    }

    public function testDataReferences()
    {
        $data = array(
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => array('posts' => 'are cool', 'another field' => 'to serialize')
        );

        $post = new Entity_Post($data);

        $title = $post->title;

        $this->assertEquals($title, $post->title);

        $title = 'asdf';

        $this->assertEquals('A Post', $post->title);

        $this->assertEquals('asdf', $title);

        // Test implicit 
        $this->assertNull($post->date_created);

        $post->date_created = null;

        $this->assertNull($post->date_created);

        $post->data['posts'] = 'are really cool';

        $this->assertEquals($post->data, array('posts' => 'are really cool', 'another field' => 'to serialize'));

        $data =& $post->data;

        $data['posts'] = 'are still cool';

        $this->assertEquals($post->data, array('posts' => 'are still cool', 'another field' => 'to serialize'));
    }
}
