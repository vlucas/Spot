<?php
namespace Spot\Type;
use Spot\Entity;

class Date extends DateTime
{
    public static $_adapterType = 'date';
    public static $_format = 'Y-m-d';
}
