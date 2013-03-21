<?php
namespace Spot\Type;
use Spot\Entity;

interface TypeInterface
{
    public static function cast($value);
    public static function get(Entity $entity, $name);
    public static function set(Entity $entity, $name);
    public static function _dump($value);
    public static function dump($value);
    public static function _load($value);
    public static function load($value);
}
