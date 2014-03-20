<?php
/**
 * Event Search Index
 *
 * @package Spot
 */
class Entity_Event_Search extends \Spot\Entity
{
    protected static $_datasource = 'test_events_search';
    // MyISAM table for FULLTEXT searching
    protected static $_datasourceOptions = array(
        'engine' => 'MyISAM'
    );


    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'event_id' => array('type' => 'int', 'index' => true, 'required' => true),
            'body' => array('type' => 'text', 'required' => true, 'fulltext' => true)
        );
    }

    public static function relations() {
      return array(
          // Reference back to event
          'event' => array(
              'type' => 'HasOne',
              'entity' => 'Entity_Event',
              'where' => array('id' => ':entity.event_id')
          )
      );
    }
}
