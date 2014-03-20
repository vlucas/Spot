<?php
/**
 * Post
 *
 * @package Spot
 */
class Entity_Event extends \Spot\Entity
{
    protected static $_datasource = 'test_events';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'title' => array('type' => 'string', 'required' => true),
            'description' => array('type' => 'text', 'required' => true),
            'type' => array('type' => 'string', 'required' => true, 'options' => array(
                'free' => 'Free',
                'private' => 'Private (Ticket Required)',
                'vip' => 'VIPs only'
            )),
            'token' => array('type' => 'string', 'required' => true),
            'date_start' => array('type' => 'datetime', 'required' => true, 'validation' => array(
                'dateAfter' => new \DateTime()
            )),
            'date_created' => array('type' => 'datetime')
        );
    }

    public static function hooks()
    {
        return array(
            'beforeInsert' => array('hookGenerateToken'),
            'afterSave' => array('hookUpdateSearchIndex')
        );
    }

    public function hookGenerateToken(\Spot\Mapper $mapper) {
        $this->token = uniqid();
    }

    public function hookUpdateSearchIndex(\Spot\Mapper $mapper) {
        $result = $mapper->upsert('Entity_Event_Search', array(
            'event_id' => $this->id,
            'body'     => $this->title . ' ' . $this->description
        ), array(
            'event_id' => $this->id
        ));
    }
}
