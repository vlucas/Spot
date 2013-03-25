<?php
namespace Spot;
use Spot\Entity;

class Type implements Type\TypeInterface
{
    public static $_loadHandlers = array();
    public static $_dumpHandlers = array();
    public static $_defaultType = 'string';
    public static $_defaultOptions = array();


    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        return $value;
    }

    /**
     * Geting value off Entity object
     */
    public static function get(Entity $entity, $value)
    {
        return static::cast($value);
    }

    /**
     * Setting value on Entity object
     */
    public static function set(Entity $entity, $value)
    {
        return static::cast($value);
    }

    /**
     * Load value as passed from the datasource
     * internal to allow for extending on a per-adapter basis
     */
    public static function _load($value, $adapter = null) {
        if (isset(static::$_loadHandlers[$adapter]) && is_callable(static::$_loadHandlers[$adapter])) {
            return call_user_func(static::$_loadHandlers[$adapter], $value);
        }
        return static::load($value);
    }

    /**
     * Load value as passed from the datasource
     */
    public static function load($value) {
        return static::cast($value);
    }

    /**
     * Dumps value as passed to the datasource
     * internal to allow for extending on a per-adapter basis
     */
    public static function _dump($value, $adapter = null) {
        if (isset(static::$_dumpHandlers[$adapter]) && is_callable(static::$_dumpHandlers[$adapter])) {
            return call_user_func(static::$_dumpHandlers[$adapter], $value);
        }
        return static::dump($value);
    }

    /**
     * Dump value as passed to the datasource
     */
    public static function dump($value) {
        return static::cast($value);
    }
}
