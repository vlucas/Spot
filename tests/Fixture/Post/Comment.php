<?php
/**
 * Post Comment
 * @todo implement 'BelongsTo' relation for linking back to blog post object
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Fixture_Post_Comment
{
	protected $_datasource = 'test_post_comments';

	public $id = array('type' => 'int', 'primary' => true, 'serial' => true);
	public $post_id = array('type' => 'int', 'index' => true, 'required' => true);
	public $name = array('type' => 'string', 'required' => true);
	public $email = array('type' => 'string', 'required' => true);
	public $body = array('type' => 'text', 'required' => true);
	public $date_created = array('type' => 'datetime');
}