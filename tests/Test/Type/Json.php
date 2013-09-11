<?php
namespace Test\Type;
use Spot\Type;

class Json extends Type
{
    public static $_adapterType = 'string';

    public static function load($value)
    {
        // Usually would 'json_decode' here, but we don't for testing (to ensure string has been json_encoded)
        return $value;
    }

    public static function dump($value)
    {
        return json_encode($value);
    }
}

