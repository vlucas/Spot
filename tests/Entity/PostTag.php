<?php
/**
 * PostTag
 *
 * @package Spot
 */
class Entity_PostTag extends \Spot\Entity
{
    protected static $_datasource = 'test_posttags';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'tag_id' => array('type' => 'int', 'required' => true),
            'post_id' => array('type' => 'int', 'required' => true),
        );
    }

    public static function relations()
    {
        return array(
            // Each post tag entity 'hasMany' post and tag entities
            'posts' => array(
                'type' => 'HasManyThrough',
                'entity' => 'Entity_Post',
                'where' => array('post_id' => ':entity.id'),
            ),
            'tags' => array(
                'type' => 'HasManyThrough',
                'entity' => 'Entity_Tag',
                'where' => array('tag_id' => ':entity.id'),
            )
        );
    }
}
