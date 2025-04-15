<?php

namespace EventMesh\LaravelSdk\Tests\Saga;

use EventMesh\LaravelSdk\Saga\SagaManager;
use EventMesh\LaravelSdk\Storage\SqlSagaStorage;
use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;

class SagaManagerTest extends TestCase
{
    private SagaManager $sagaManager;
    private SqlSagaStorage $storage;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up the database
        $this->createSagaLogsTable();
        
        // Create the storage
        $this->storage = new SqlSagaStorage();
        
        // Create a mock EventMeshManager
        $eventMeshManager = $this->createMock(EventMeshManager::class);
        $eventMeshManager->method('publish')
            ->willReturn(true);
        
        // Create the SagaManager
        $this->sagaManager = new SagaManager($eventMeshManager, [
            'retry_attempts' => 3,
            'retry_delay' => 60,
            'compensation_handlers' => [
                'payment.failed' => 'App\\Events\\CompensateOrder',
            ],
        ]);
    }
    
    protected function createSagaLogsTable()
    {
        DB::statement('DROP TABLE IF EXISTS saga_logs');
        
        DB::statement('
            CREATE TABLE saga_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                saga_instance_id VARCHAR(255) NOT NULL,
                event_name VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL,
                payload JSON,
                headers JSON,
                retry_count INT UNSIGNED DEFAULT 0,
                error_message TEXT,
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX saga_instance_id_index (saga_instance_id),
                INDEX event_name_index (event_name)
            )
        ');
    }
    
    public function testStartSaga()
    {
        // Start a saga
        $sagaInstanceId = $this->sagaManager->start('order.created', [
            'order_id' => 123,
            'amount' => 99.99,
        ]);
        
        // Verify the saga instance ID is a valid UUID
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $sagaInstanceId);
        
        // Verify the saga log was created
        $log = DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->where('event_name', 'order.created')
            ->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->status);
        $this->assertEquals(json_encode(['order_id' => 123, 'amount' => 99.99]), $log->payload);
    }
    
    public function testHandleEvent()
    {
        // Start a saga
        $sagaInstanceId = $this->sagaManager->start('order.created', [
            'order_id' => 123,
            'amount' => 99.99,
        ]);
        
        // Handle an event
        $this->sagaManager->handleEvent($sagaInstanceId, 'payment.processed', [
            'status' => 'success',
            'transaction_id' => 'tx_123',
        ]);
        
        // Verify the event was processed
        $log = DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->where('event_name', 'payment.processed')
            ->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(json_encode(['status' => 'success', 'transaction_id' => 'tx_123']), $log->payload);
    }
    
    public function testGetSagaStatus()
    {
        // Start a saga
        $sagaInstanceId = $this->sagaManager->start('order.created', [
            'order_id' => 123,
            'amount' => 99.99,
        ]);
        
        // Handle an event
        $this->sagaManager->handleEvent($sagaInstanceId, 'payment.processed', [
            'status' => 'success',
            'transaction_id' => 'tx_123',
        ]);
        
        // Get the saga status
        $status = $this->sagaManager->getSagaStatus($sagaInstanceId);
        
        // Verify the status
        $this->assertEquals($sagaInstanceId, $status['saga_instance_id']);
        $this->assertCount(2, $status['steps']);
        $this->assertEquals('pending', $status['steps'][0]['status']);
        $this->assertEquals('success', $status['steps'][1]['status']);
    }
    
    public function testTriggerCompensation()
    {
        // Register a mock event listener for the compensation event
        $compensationTriggered = false;
        Event::listen('App\\Events\\CompensateOrder', function ($event) use (&$compensationTriggered) {
            $compensationTriggered = true;
        });
        
        // Start a saga
        $sagaInstanceId = $this->sagaManager->start('order.created', [
            'order_id' => 123,
            'amount' => 99.99,
        ]);
        
        // Simulate a failed event with multiple retries
        for ($i = 0; $i < 3; $i++) {
            $this->sagaManager->handleEvent($sagaInstanceId, 'payment.failed', [
                'error' => 'Payment failed',
            ]);
        }
        
        // Verify the compensation was triggered
        $this->assertTrue($compensationTriggered);
    }
    
    protected function tearDown(): void
    {
        // Clean up the database
        DB::statement('DROP TABLE IF EXISTS saga_logs');
        
        parent::tearDown();
    }
} 