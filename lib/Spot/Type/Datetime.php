<?php
namespace Spot\Type;
use Spot\Entity;

class Datetime extends \Spot\Type
{
    public static $_defaultType = 'datetime';
    public static $_format = 'Y-m-d H:i:s';

    /**
     * Cast given value to type required
     */
    public static function cast($value)
    {
        if(is_string($value) || is_numeric($value)) {
            // Create new \DateTime instance from string value
            if (is_numeric($value)) {
              $value = new \DateTime('@' . $value);
            } else if ($value) {
              $value = new \DateTime($value);
            } else {
              $value = null;
            }
        }
        return $value;
    }

    public static function dump($value)
    {
        $value = static::cast($value);
        if ($value) {
            $value = $value->format(static::$_format);
        }
        return $value;
    }
}
