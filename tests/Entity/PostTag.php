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
            'tag_id' => array('type' => 'int', 'required' => true, 'unique' => 'post_tag'),
            'post_id' => array('type' => 'int', 'required' => true, 'unique' => 'post_tag'),
            'random' => array('type' => 'string') // Totally unnecessary, but makes testing upserts easy
        );
    }

    public static function relations()
    {
        return array(
            // Each post tag entity 'HasOne' post and tag entity
            'post' => array(
                'type' => 'HasOne',
                'entity' => 'Entity_Post',
                'where' => array('post_id' => ':entity.id'),
            ),
            'tag' => array(
                'type' => 'HasOne',
                'entity' => 'Entity_Tag',
                'where' => array('tag_id' => ':entity.id'),
            )
        );
    }
}
