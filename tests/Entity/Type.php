<?php
/**
 * Types
 * Exists solely for the purpose of testing custom types
 *
 * @package Spot
 */
class Entity_Type extends \Spot\Entity
{
    protected static $_datasource = 'test_types';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'serialized' => array('type' => 'serialized', 'required' => true),
            'date_created' => array('type' => 'datetime')
        );
    }
}

