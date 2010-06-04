<?php
/**
 * @package Spot
 * @link http://spot.os.ly
 */
class Spot_Tests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Spot Tests');

		// Traverse the "Test" directory and add the files as tests
		$path = dirname(__FILE__);
		foreach (glob($path."/Test/*.php") as $filename)
		{
			$pathParts = pathinfo($filename);
			$suite->addTestSuite('Test_'.$pathParts['filename']);
		}
        return $suite;
    }
}
