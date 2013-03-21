<?php
namespace Spot\Type;
use Spot\Entity;

class Date extends DateTime
{
    public static $_defaultType = 'date';
    public static $_format = 'Y-m-d';
}
