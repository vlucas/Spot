<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Test_Mapper extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    protected function setUp()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_Post');
        $mapper->migrate('Entity_Post_Comment');
    }

    protected function tearDown()
    {
        $mapper = test_spot_mapper();
        $mapper->truncateDatasource('Entity_Post');
        $mapper->truncateDatasource('Entity_Post_Comment');
    }

    public function testLoadRelations()
    {
        $mapper = test_spot_mapper();

        $post = new Entity_Post();

        $relation = $mapper->loadRelation($post, 'comments');

        $this->assertInstanceOf('\\Spot\\Relation\\HasMany', $relation);

        $posts = array(new Entity_Post(), new Entity_Post());

        $collection = new \Spot\Entity\Collection($posts, array(), 'Entity_Post');

        $relation = $mapper->loadRelation($collection, 'comments');

        $this->assertEquals($relation->entityName(), 'Entity_Post_Comment');

        $this->assertInstanceOf('\\Spot\\Relation\\HasMany', $mapper->loadRelation($collection, 'comments'));
    }

    public function testRelationConditions()
    {
        $mapper = test_spot_mapper();

        for( $i = 1; $i <= 10; $i++ ) {
            $id = $mapper->insert('Entity_Post', array(
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => $mapper->connection('Entity_Post')->dateTime()
            ));
            for( $j = 1; $j <= 2; $j++ ) {
                $mapper->insert('Entity_Post_Comment', array(
                    'post_id' => $id,
                    'name' => ($j % 2 ? 'odd' : 'even' ). '_title',
                    'email' => 'bob@somewhere.com'
                ));
            }
        }

        $post = $mapper->all('Entity_Post')->first();

        $relation = $mapper->loadRelation($post, 'comments');

        $this->assertEquals($relation->conditions(), array('post_id' => 1));

        $posts = $mapper->all('Entity_Post')->execute();

        $relation = $mapper->loadRelation($posts, 'comments');

        $this->assertEquals($relation->conditions(), array('post_id' => array(1,2,3,4,5,6,7,8,9,10)));
    }
}