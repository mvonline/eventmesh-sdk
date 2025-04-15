<?php

namespace EventMesh\LaravelSdk\Tests\Storage;

use PHPUnit\Framework\TestCase;
use EventMesh\LaravelSdk\Contracts\SagaStorageInterface;

abstract class StorageTestCase extends TestCase
{
    protected SagaStorageInterface $storage;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = $this->createStorage();
    }
    
    abstract protected function createStorage(): SagaStorageInterface;
    
    protected function getTestData(): array
    {
        return [
            'saga_instance_id' => 'test-saga-' . uniqid(),
            'event_name' => 'test.event',
            'status' => 'pending',
            'payload' => ['test' => 'data'],
            'headers' => ['X-Test' => 'value'],
            'retry_count' => 0,
            'error_message' => null,
            'processed_at' => null,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ];
    }
    
    public function testStoreAndRetrieveLog()
    {
        $data = $this->getTestData();
        
        // Store the log
        $result = $this->storage->store($data);
        $this->assertTrue($result);
        
        // Retrieve the log
        $log = $this->storage->getLog($data['saga_instance_id'], $data['event_name']);
        
        // Verify the log
        $this->assertNotNull($log);
        $this->assertEquals($data['saga_instance_id'], $log['saga_instance_id']);
        $this->assertEquals($data['event_name'], $log['event_name']);
        $this->assertEquals($data['status'], $log['status']);
        $this->assertEquals($data['payload'], $log['payload']);
        $this->assertEquals($data['headers'], $log['headers']);
    }
    
    public function testUpdateLog()
    {
        $data = $this->getTestData();
        
        // Store the log
        $this->storage->store($data);
        
        // Update the log
        $updateData = [
            'status' => 'completed',
            'processed_at' => new \DateTime(),
            'payload' => ['updated' => 'data'],
        ];
        
        $result = $this->storage->update($data['saga_instance_id'], $data['event_name'], $updateData);
        $this->assertTrue($result);
        
        // Retrieve the updated log
        $log = $this->storage->getLog($data['saga_instance_id'], $data['event_name']);
        
        // Verify the updated log
        $this->assertNotNull($log);
        $this->assertEquals('completed', $log['status']);
        $this->assertEquals(['updated' => 'data'], $log['payload']);
    }
    
    public function testGetLogs()
    {
        $sagaInstanceId = 'test-saga-' . uniqid();
        
        // Store multiple logs for the same saga instance
        $events = ['event1', 'event2', 'event3'];
        foreach ($events as $event) {
            $data = $this->getTestData();
            $data['saga_instance_id'] = $sagaInstanceId;
            $data['event_name'] = $event;
            $this->storage->store($data);
        }
        
        // Retrieve all logs for the saga instance
        $logs = $this->storage->getLogs($sagaInstanceId);
        
        // Verify the logs
        $this->assertCount(count($events), $logs);
        $this->assertEquals($sagaInstanceId, $logs[0]['saga_instance_id']);
        
        // Verify all events are present
        $retrievedEvents = array_map(function ($log) {
            return $log['event_name'];
        }, $logs);
        
        foreach ($events as $event) {
            $this->assertContains($event, $retrievedEvents);
        }
    }
    
    public function testGetNonExistentLog()
    {
        $log = $this->storage->getLog('non-existent-saga', 'non-existent-event');
        $this->assertNull($log);
    }
} 