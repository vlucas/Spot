<?php
namespace Spot\Type;
use Spot\Entity;

class Serialized extends \Spot\Type
{
    public static $_defaultType = 'serialized';

    /**
     * Cast given value to type required
     */
    public static function load($value)
    {
        if(is_string($value)) {
            $value = @unserialize($value);
        } else {
            $value = null;
        }
        return $value;
    }

    public static function dump($value)
    {
        return serialize($value);
    }
}
