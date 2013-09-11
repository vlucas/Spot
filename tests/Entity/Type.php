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

    // Declared 'public static' here so they can be modified by tests - this is for TESTING ONLY
    public static $_fields = array(
        'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
        'serialized' => array('type' => 'serialized'),
        'date_created' => array('type' => 'datetime')
    );

    public static function fields()
    {
        return self::$_fields;
    }
}

