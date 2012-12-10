<?php
/**
 * @package Spot
 */
class Test_Validation extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_User');
    }
    public static function tearDownAfterClass()
    {
        $mapper = test_spot_mapper();
        $mapper->truncateDatasource('Entity_User');
    }

    public function testUniqueFieldCreatesValidationError()
    {
        $mapper = test_spot_mapper();

        // Setup new user
        $user1 = new Entity_User(array(
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => true
        ));
        $mapper->save($user1);

        // Setup new user (identical, expecting a validation error)
        $user2 = new Entity_User(array(
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => false
        ));
        $mapper->save($user2);

        $this->assertFalse($user1->hasErrors());
        $this->assertTrue($user2->hasErrors());
        $this->assertEquals($user2->errors('email'), array("Email 'test@test.com' is already taken."));
    }
}
