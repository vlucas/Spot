<?php
/**
 * Post Comment
 * @todo implement 'BelongsTo' relation for linking back to blog post object
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Entity_Post_Comment extends \Spot\Entity
{
    protected static $_datasource = 'test_post_comments';
    
    protected $id = array('type' => 'int', 'primary' => true, 'serial' => true);
    protected $post_id = array('type' => 'int', 'index' => true, 'required' => true);
    protected $name = array('type' => 'string', 'required' => true);
    protected $email = array('type' => 'string', 'required' => true);
    protected $body = array('type' => 'text', 'required' => true);
    protected $date_created = array('type' => 'datetime');
}