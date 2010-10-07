<?php
/**
 * Post 
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Entity_Post extends \Spot\Entity
{
    protected static $_datasource = 'test_posts';

    protected $id = array('type' => 'int', 'primary' => true, 'serial' => true);
    protected $title = array('type' => 'string', 'required' => true);
    protected $body = array('type' => 'text', 'required' => true);
    protected $status = array('type' => 'int', 'default' => 0, 'index' => true);
    protected $date_created = array('type' => 'datetime');
    
    // Each post entity 'hasMany' comment entites
    protected $comments = array(
        'type' => 'relation',
        'relation' => 'HasMany',
        'entity' => 'Entity_Post_Comment',
        'where' => array('post_id' => ':entity.id'),
        'order' => array('date_created' => 'ASC')
    );
}
