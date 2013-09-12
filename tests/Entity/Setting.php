<?php
/**
 * Setting
 *
 * @package Spot
 */
class Entity_Setting extends \Spot\Entity
{
    protected static $_datasource = 'test_settings';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'skey' => array('type' => 'string', 'required' => true, 'unique' => true),
            'svalue' => array('type' => 'encrypted',  'required' => true)
        );
    }
}

