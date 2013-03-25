<?php
namespace Spot\Type;
use Spot\Entity;

class Integer extends \Spot\Type
{
    public static $_defaultType = 'integer';

    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        if(strlen($value)) {
            return (int) $value;
        }
        return null;
    }
}
