<?php
/**
 * @package Spot
 */
class Test_Transactions extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_Post');
    }

    public function testInsertWithTransaction()
    {
        $post = new Entity_Post();
        $mapper = test_spot_mapper();
        $post->title = "Test Post with Transaction";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = $mapper->connection('Entity_Post')->dateTime();

        // Save in transation
        $phpunit = $this;
        $mapper->transaction(function($mapper) use($post, $phpunit) {
            $result = $mapper->insert($post);
        });

        // Ensure save was successful
        $this->assertInstanceOf('Entity_Post', $mapper->first('Entity_Post', array('title' => $post->title)));
    }

    public function testInsertWithTransactionRollbackOnException()
    {
        $post = new Entity_Post();
        $mapper = test_spot_mapper();
        $post->title = "Rolledback";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = $mapper->connection('Entity_Post')->dateTime();

        // Save in transation
        $phpunit = $this;

        try {
            $mapper->transaction(function($mapper) use($post, $phpunit) {
                $result = $mapper->insert($post);

                // Throw exception AFTER save to trigger rollback
                throw new LogicException("Exceptions should trigger auto-rollback");
            });
        } catch(LogicException $e) {
            // Ensure record was NOT saved
            $this->assertFalse($mapper->first('Entity_Post', array('title' => $post->title)));
        }
    }

    public function testInsertWithTransactionRollbackOnReturnFalse()
    {
        $post = new Entity_Post();
        $mapper = test_spot_mapper();
        $post->title = "Rolledback";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = $mapper->connection('Entity_Post')->dateTime();

        // Save in transation
        $phpunit = $this;

        $mapper->transaction(function($mapper) use($post, $phpunit) {
            $result = $mapper->insert($post);

            // Return false AFTER save to trigger rollback
            return false;
        });

        // Ensure record was NOT saved
        $this->assertFalse($mapper->first('Entity_Post', array('title' => $post->title)));
    }
}
