<?php
/**
 * @package Spot
 */
class Test_Hooks extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

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
                'date_created' => new \DateTime(),
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

    protected function tearDown()
    {
        $mapper = test_spot_mapper();
        $mapper->off('Entity_Post', true);
        Entity_Post::$hooks = array();
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

        $mapper->on('Entity_Post', 'beforeSave', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeSave';
        });

        $mapper->on('Entity_Post', 'afterSave', function($post, $mapper, $result) use (&$hooks, &$testcase) {
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
        
        $mapper->on('Entity_Post', 'beforeInsert', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeInsert';
        });

        $mapper->on('Entity_Post', 'afterInsert', function($post, $mapper, $result) use (&$hooks, &$testcase) {
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

        $mapper->on('Entity_Post', 'beforeInsert', function($post, $mapper) use (&$testcase) {
            $testcase->assertTrue(false);
        });

        $mapper->on('Entity_Post', 'beforeUpdate', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeUpdate';
        });

        $mapper->on('Entity_Post', 'afterUpdate', function($post, $mapper, $result) use (&$hooks, &$testcase) {
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

        $mapper->on('Entity_Post', 'beforeDelete', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeDelete';
        });

        $mapper->on('Entity_Post', 'afterDelete', function($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array('called beforeDelete'));
            $hooks[] = 'called afterDelete';
        });

        $this->assertEquals($hooks, array());

        $mapper->delete($post);

        $this->assertEquals($hooks, array('called beforeDelete', 'called afterDelete'));
    }


    public function testEntityHooks()
    {
        $mapper = test_spot_mapper();
        $post = new Entity_Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $i = $post->status;

        Entity_Post::$hooks = array(
            'beforeSave' => array('mock_save_hook')
        );

        $mapper->save($post);

        $this->assertEquals($i + 1, $post->status);

        Entity_Post::$hooks = array(
            'beforeSave' => array('mock_save_hook', 'mock_save_hook')
        );

        $i = $post->status;

        $mapper->save($post);

        $this->assertEquals($i + 2, $post->status);
    }


    public function testWithHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;

        $hooks = array();

        $mapper->on('Entity_Post', 'beforeWith', function($entityClass, $collection, $with, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals('Entity_Post', $entityClass);
            $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
            $testcase->assertEquals(array('comments'), $with);
            $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
            $hooks[] = 'Called beforeWith';
        });

        $mapper->on('Entity_Post', 'loadWith', function($entityClass, $collection, $relationName, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals('Entity_Post', $entityClass);
            $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
            $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
            $testcase->assertEquals('comments', $relationName);
            $hooks[] = 'Called loadWith';
        });

        $mapper->on('Entity_Post', 'afterWith', function($entityClass, $collection, $with, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals('Entity_Post', $entityClass);
            $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
            $testcase->assertEquals(array('comments'), $with);
            $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
            $hooks[] = 'Called afterWith';
        });

        $mapper->all('Entity_Post', array('id' => array(1,2)))->with('comments')->execute();

        $this->assertEquals(array('Called beforeWith', 'Called loadWith', 'Called afterWith'), $hooks);
    }


    public function testWithAssignmentHooks()
    {
        $mapper = test_spot_mapper();
        $testcase = $this;
        
        $mapper->on('Entity_Post', 'loadWith', function($entityClass, $collection, $relationName, $mapper) use (&$testcase) {
            $relationObj = $mapper->loadRelation($collection, $relationName);
            $query = $relationObj->execute()->limit(1)->snapshot();

            foreach($collection as $post) {
                $one_comment = $query->execute();
                
                $post->comments->assignCollection($one_comment);
                $testcase->assertEquals(1, $post->comments->count());
            }
            return false;
        });

        $posts = $mapper->all('Entity_Post')->with('comments')->execute();
        foreach($posts as $post) {
            $this->assertEquals(1, $post->comments->count());
        }
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


    public function testMapperChaining()
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
        })->on('Entity_Post', 'afterSave', function($post, $mapper, $result) use (&$hooks) {
            $hooks[] = 'called afterSave';
        });

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeSave', 'called afterSave'));

        $mapper->off('Entity_Post', 'beforeSave')->off('Entity_Post', 'afterSave');

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeSave', 'called afterSave'));
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidCallable()
    {
        $mapper = test_spot_mapper();
        $mapper->on('Entity_Post', 'beforeSave', 'asdf');
        $mapper->on('Entity_Post', 'beforeSave', array($this, 'asdf'));
        
        $this->assertEquals(array(), $mapper->getHooks('Entity_Post', 'beforeSave'));
    }
    
    public function testInvalidCallablesArentMapped()
    {
        $mapper = test_spot_mapper();
        try {
            $mapper->on('Entity_Post', 'beforeSave', 'asdf');
            $mapper->on('Entity_Post', 'beforeSave', array($this, 'asdf'));
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        $this->assertEquals(array(), $mapper->getHooks('Entity_Post', 'beforeSave'));
    }
}
