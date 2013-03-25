<?php
namespace Spot\Type;
use Spot\Entity;

class Boolean extends \Spot\Type
{
    public static $_defaultType = 'boolean';

    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        return (bool) $value;
    }

    /**
     * Boolean is generally persisted as an integer
     */
    public static function dump($value)
    {
        return (int) $value;
    }
}
