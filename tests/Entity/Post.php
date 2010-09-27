<?php
/**
 * Post 
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Entity_Post extends \Spot\Entity\EntityAbstract
{
	protected static $_datasource = 'test_posts';

    /**
     * Describe fields
     *
     * @return array
     */
    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime')
        );
    }
    
    /**
     * Describe relations
     *
     * @return array
     */
    public static function relations()
    {
        // Each post entity 'hasMany' comment entites
        return array(
            'comments' => array(
                'type' => 'relation',
                'relation' => 'HasMany',
                'entity' => 'Entity_Post_Comment',
                'where' => array('post_id' => ':entity.id'),
                'order' => array('date_created' => 'ASC')
            )
        );
    }
}
