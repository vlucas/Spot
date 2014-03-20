<?php
/**
 * @package Spot
 */
class Test_Search extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        $mapper->migrate('Entity_Event');
        $mapper->migrate('Entity_Event_Search');
    }

    public function testInsertEvent()
    {
        $mapper = test_spot_mapper();
        $event = new Entity_Event(array(
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'type' => 'free',
            'date_start' => strtotime('+1 day')
        ));
        $result = $mapper->insert($event);

        $this->assertInternalType('integer', $event->id);
    }

    public function testEventSearchIndex()
    {
        $mapper = test_spot_mapper();
        if (!$mapper->config()->connection() instanceof \Spot\Adapter\Mysql) {
            $this->markTestSkipped('Only supported in MySQL - requires FULLTEXT search');
        }

        $event = new Entity_Event(array(
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'type' => 'free',
            'date_start' => strtotime('+1 day')
        ));
        $mapper->save($event);

        // Ensure Event_Search record was inserted with 'afterSave' hook
        $eventSearchEntity = $mapper->first('Entity_Event_Search', array('event_id' => $event->id));
        $this->assertInstanceOf('Entity_Event_Search', $eventSearchEntity);

        $events = $mapper->all('Entity_Event_Search')
            ->search('body', 'Test', array('boolean' => true)) // MySQL Boolean mode
            ->execute();
        $this->assertGreaterThan(0, count($events));
    }
}

