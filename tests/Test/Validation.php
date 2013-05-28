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
        $mapper->migrate('Entity_Author');
    }
    public static function tearDownAfterClass()
    {
        $mapper = test_spot_mapper();
        $mapper->truncateDatasource('Entity_Author');
    }

    public function tearDown()
    {
        $mapper = test_spot_mapper();
        $mapper->truncateDatasource('Entity_Author');
    }

    public function testRequiredField()
    {
        $mapper = test_spot_mapper();

        $entity = new Entity_Author(array(
            'is_admin' => true
        ));
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Required", $entity->errors('email'));
    }

    public function testUniqueField()
    {
        $mapper = test_spot_mapper();

        // Setup new user
        $user1 = new Entity_Author(array(
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => true
        ));
        $mapper->save($user1);

        // Setup new user (identical, expecting a validation error)
        $user2 = new Entity_Author(array(
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => false
        ));
        $mapper->save($user2);

        $this->assertFalse($user1->hasErrors());
        $this->assertTrue($user2->hasErrors());
        $this->assertContains("Email 'test@test.com' is already taken.", $user2->errors('email'));
    }

    public function testEmail()
    {
        $mapper = test_spot_mapper();

        $entity = new Entity_Author(array(
            'email' => 'test',
            'password' => 'test'
        ));
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Invalid email address", $entity->errors('email'));
    }

    public function testLength()
    {
        $mapper = test_spot_mapper();

        $entity = new Entity_Author(array(
            'email' => 't@t',
            'password' => 'test'
        ));
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Must be longer than 4", $entity->errors('email'));
    }
}
