<?php
/**
 * Post
 *
 * @package Spot
 */
class Entity_Tag extends \Spot\Entity
{
    protected static $_datasource = 'test_tags';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'name' => array('type' => 'string', 'required' => true),
        );
    }

    public static function relations()
    {
        return array(
            // Each tag entity 'hasManyThrough' post entities
            'posts' => array(
                'type' => 'HasManyThrough',
                'entity' => 'Entity_Post',
                'throughEntity' => 'Entity_PostTag',
                'throughWhere' => array('tag_id' => ':entity.id'),
                'where' => array('id' => ':throughEntity.post_id'),
            )
        );
    }
}
