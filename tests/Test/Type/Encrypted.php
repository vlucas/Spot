<?php
namespace Test\Type;
use Spot\Type;

class Encrypted extends Type
{
    public static $_key;
    public static function load($value)
    {
        $key = self::$_key;
        if(is_string($value)) {
            $value = self::aes256_decrypt($key, base64_decode($value));
        } else {
            $value = null;
        }
        return $value;
    }

    public static function dump($value)
    {
        $key = self::$_key;
        return base64_encode(self::aes256_encrypt($key, $value));
    }

    public static function aes256_encrypt($key, $data)
    {
      if(32 !== strlen($key)) $key = hash('SHA256', $key, true);
      $padding = 16 - (strlen($data) % 16);
      $data .= str_repeat(chr($padding), $padding);
      return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16));
    }

    public static function aes256_decrypt($key, $data)
    {
      if(32 !== strlen($key)) $key = hash('SHA256', $key, true);
      $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, str_repeat("\0", 16));
      $padding = ord($data[strlen($data) - 1]);
      return substr($data, 0, -$padding);
    }
}

