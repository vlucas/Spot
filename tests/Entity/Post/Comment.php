<?php
/**
 * Post Comment
 * @todo implement 'BelongsTo' relation for linking back to blog post object
 *
 * @package Spot
 */
class Entity_Post_Comment extends \Spot\Entity
{
    protected static $_datasource = 'test_post_comments';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'post_id' => array('type' => 'int', 'index' => true, 'required' => true),
            'name' => array('type' => 'string', 'required' => true),
            'email' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'date_created' => array('type' => 'datetime')
        );
    }
    
    public static function relations() {
      return array(
          // Each post entity 'hasMany' comment entites
          'post' => array(
              'type' => 'HasOne',
              'entity' => 'Entity_Post',
              'where' => array('id' => ':entity.post_id')
          )
      );
    }
}
