<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Hooks extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    protected function tearDown()
    {
        $mapper = test_spot_mapper();
        $mapper->off('Entity_Post', true);
    }

    public function testSaveHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;

        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $hooks = array();

        $mapper->on('Entity_Post', 'beforeSave', function($post, $mapper) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeSave';
        });

        $mapper->on('Entity_Post', 'afterSave', function($post, $mapper, $result) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array('called beforeSave'));
            $testcase->assertInstanceOf('Entity_Post', $post);
            $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
            $hooks[] = 'called afterSave';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeSave', 'called afterSave'));

        $mapper->off('Entity_Post', array('afterSave', 'beforeSave'));

        $mapper->save($post);

        // Verify that hooks were deregistered
        $this->assertEquals($hooks, array('called beforeSave', 'called afterSave'));
    }

    public function testInsertHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;

        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $hooks = array();
        
        $mapper->on('Entity_Post', 'beforeInsert', function($post, $mapper) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeInsert';
        });

        $mapper->on('Entity_Post', 'afterInsert', function($post, $mapper, $result) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array('called beforeInsert'));
            $hooks[] = 'called afterInsert';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeInsert', 'called afterInsert'));
    }

    public function testUpdateHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;

        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));
        $mapper->save($post);

        $hooks = array();

        $mapper->on('Entity_Post', 'beforeInsert', function($post, $mapper) use ($testcase) {
            $testcase->assertTrue(false);
        });

        $mapper->on('Entity_Post', 'beforeUpdate', function($post, $mapper) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeUpdate';
        });

        $mapper->on('Entity_Post', 'afterUpdate', function($post, $mapper, $result) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array('called beforeUpdate'));
            $hooks[] = 'called afterUpdate';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeUpdate', 'called afterUpdate'));
    }


    public function testDeleteHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;

        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));
        $mapper->save($post);

        $hooks = array();

        $mapper->on('Entity_Post', 'beforeDelete', function($post, $mapper) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeDelete';
        });

        $mapper->on('Entity_Post', 'afterDelete', function($post, $mapper, $result) use (&$hooks, $testcase) {
            $testcase->assertEquals($hooks, array('called beforeDelete'));
            $hooks[] = 'called afterDelete';
        });

        $this->assertEquals($hooks, array());

        $mapper->delete($post);

        $this->assertEquals($hooks, array('called beforeDelete', 'called afterDelete'));
    }


    public function testHookReturnsFalse()
    {
        $mapper = test_spot_mapper();
        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $hooks = array();

        $mapper->on('Entity_Post', 'beforeSave', function($post, $mapper) use (&$hooks) {
            $hooks[] = 'called beforeSave';
            return false;
        });

        $mapper->on('Entity_Post', 'afterSave', function($post, $mapper, $result) use (&$hooks) {
            $hooks[] = 'called afterSave';
        });

        $mapper->save($post);

        $mapper->off('Entity_Post', array('beforeSave', 'afterSave'));

        $this->assertEquals($hooks, array('called beforeSave'));
    }
}