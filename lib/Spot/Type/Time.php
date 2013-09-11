<?php
namespace Spot\Type;
use Spot\Entity;

class Time extends DateTime
{
    public static $_adapterType = 'time';
    public static $_format = 'H:i:s';
}
