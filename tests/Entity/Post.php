<?php
/**
 * Post
 *
 * @package Spot
 */
class Entity_Post extends \Spot\Entity
{
    protected static $_datasource = 'test_posts';
    // For testing purposes only
    public static $hooks = array();

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime'),
            'data' => array('type' => 'serialized'),
            'author_id' => array('type' => 'int', 'required' => true),
        );
    }

    public static function relations()
    {
        return array(
            // Each post entity 'hasMany' comment entites
            'comments' => array(
                'type' => 'HasMany',
                'entity' => 'Entity_Post_Comment',
                'where' => array('post_id' => ':entity.id'),
                'order' => array('date_created' => 'ASC')
            ),
            // Each post entity 'hasManyThrough' tag entities
            'tags' => array(
                'type' => 'HasManyThrough',
                'entity' => 'Entity_Tag',
                'throughEntity' => 'Entity_PostTag',
                'throughWhere' => array('post_id' => ':entity.id'),
                'where' => array('id' => ':throughEntity.tag_id'),
            ),
            // Each post entity 'hasOne' author entites
            'author' => array(
                'type' => 'HasOne',
                'entity' => 'Entity_Author',
                'where' => array('id' => ':entity.author_id')
            ),
        );
    }

    public static function hooks()
    {
        return static::$hooks;
    }
    
    public function mock_save_hook()
    {
        $this->status++;
    }
}
