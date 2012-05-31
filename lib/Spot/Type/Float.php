<?php
namespace Spot\Type;
use Spot\Entity;

class Float implements TypeInterface
{
    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        return (float) $value;
    }

    /**
     * Geting value off Entity object
     */
    public static function get(Entity $entity, $value)
    {
        return self::cast($value);
    }

    /**
     * Setting value on Entity object
     */
    public static function set(Entity $entity, $value)
    {
        return self::cast($value);
    }
}
