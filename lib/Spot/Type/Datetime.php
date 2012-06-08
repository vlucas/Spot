<?php
namespace Spot\Type;
use Spot\Entity;

class Datetime implements TypeInterface
{
    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        if($value && (is_string($value) || is_numeric($value))) {
            // Create new \DateTime instance from string value
            $value = new \DateTime($value);
        }
        return $value;
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
