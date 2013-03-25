<?php
namespace Spot\Type;
use Spot\Entity;

class String extends \Spot\Type
{
    public static $_defaultType = 'string';

    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        if(null !== $value) {
            return (string) $value;
        }
        return $value;
    }
}
