<?php
/**
 * @package Spot
 */
class Test_Types extends PHPUnit_Framework_TestCase
{

  public function testInteger()
  {
        $cls = 'Spot\\Type\\Integer';

        $this->assertSame($cls::cast(0), 0);

        $this->assertSame($cls::cast(0.0), 0);

        $this->assertSame($cls::cast(0.75), 0);

        $this->assertSame($cls::cast('100'), 100);

        $this->assertSame($cls::cast('-20'), -20);

        $this->assertSame($cls::cast(''), null);

        $this->assertSame($cls::cast(false), null);

        $this->assertSame($cls::cast(null), null);
    }


    public function testFloat()
    {
        $cls = 'Spot\\Type\\Float';

        $this->assertSame($cls::cast(0), 0.0);

        $this->assertNotSame($cls::cast(0.0), 0);

        $this->assertSame($cls::cast(0.0), 0.0);

        $this->assertSame($cls::cast(0.75), 0.75);

        $this->assertNotSame($cls::cast(0.75), '0.75');

        $this->assertSame($cls::cast('100.12'), 100.12);

        $this->assertSame($cls::cast('-20.45'), -20.45);

        $this->assertSame($cls::cast(''), null);

        $this->assertSame($cls::cast(false), null);

        $this->assertSame($cls::cast(null), null);
    }


    public function testString()
    {
        $cls = 'Spot\\Type\\String';

        $this->assertSame($cls::cast('abc'), 'abc');

        $this->assertSame($cls::cast('AbC'), 'AbC');

        $this->assertSame($cls::cast('0.75'), '0.75');

        $this->assertSame($cls::cast(0.75), '0.75');

        $this->assertNotSame($cls::cast(0.75), 0.75);

        $this->assertSame($cls::cast(''), '');

        $this->assertSame($cls::cast(false), '');

        $this->assertSame($cls::cast(null), null);
    }


    public function testBoolean()
    {
        $cls = 'Spot\\Type\\Boolean';

        $truths = array('a', 1, 100, 256, -12, 0.2, true);
        foreach ($truths as $truth) {
            $this->assertSame($cls::cast($truth), true);
        }

        $falses = array(false, null, '', 0, 0.0);
        foreach ($falses as $false) {
            $this->assertSame($cls::cast($false), false);
        }
    }
}
