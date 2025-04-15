<?php

namespace EventMesh\LaravelSdk\Tests\EventMesh;

use EventMesh\LaravelSdk\EventMeshManager;
use EventMesh\LaravelSdk\Facades\EventMesh;
use EventMesh\LaravelSdk\Tests\TestCase;
use Mockery;

class EventMeshFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the EventMeshManager
        $this->mock = Mockery::mock(EventMeshManager::class);
        $this->app->instance('eventmesh', $this->mock);
    }

    public function test_publish_event()
    {
        $this->mock->shouldReceive('publish')
            ->once()
            ->with('order.created', ['order_id' => 123], [])
            ->andReturn(true);

        $result = EventMesh::publish('order.created', ['order_id' => 123]);
        
        $this->assertTrue($result);
    }

    public function test_publish_event_with_headers()
    {
        $headers = ['X-Correlation-Id' => 'abc123'];
        
        $this->mock->shouldReceive('publish')
            ->once()
            ->with('order.created', ['order_id' => 123], $headers)
            ->andReturn(true);

        $result = EventMesh::publish('order.created', ['order_id' => 123], $headers);
        
        $this->assertTrue($result);
    }

    public function test_subscribe_to_topic()
    {
        $callback = function ($topic, $payload, $headers) {};
        
        $this->mock->shouldReceive('subscribe')
            ->once()
            ->with('order.created', $callback)
            ->andReturn(true);

        $result = EventMesh::subscribe('order.created', $callback);
        
        $this->assertTrue($result);
    }

    public function test_subscribe_all_events()
    {
        $callback = function ($topic, $payload, $headers) {};
        $filterPattern = 'order.*';
        
        $this->mock->shouldReceive('subscribeAll')
            ->once()
            ->with($callback, $filterPattern)
            ->andReturn(true);

        $result = EventMesh::subscribeAll($callback, $filterPattern);
        
        $this->assertTrue($result);
    }

    public function test_subscribe_all_events_without_filter()
    {
        $callback = function ($topic, $payload, $headers) {};
        
        $this->mock->shouldReceive('subscribeAll')
            ->once()
            ->with($callback, null)
            ->andReturn(true);

        $result = EventMesh::subscribeAll($callback);
        
        $this->assertTrue($result);
    }

    public function test_get_driver()
    {
        $this->mock->shouldReceive('driver')
            ->once()
            ->with(null)
            ->andReturn($this->mock);

        $driver = EventMesh::driver();
        
        $this->assertInstanceOf(EventMeshManager::class, $driver);
    }

    public function test_get_specific_driver()
    {
        $this->mock->shouldReceive('driver')
            ->once()
            ->with('mqtt')
            ->andReturn($this->mock);

        $driver = EventMesh::driver('mqtt');
        
        $this->assertInstanceOf(EventMeshManager::class, $driver);
    }

    public function test_disconnect()
    {
        $this->mock->shouldReceive('disconnect')
            ->once();

        EventMesh::disconnect();
        
        // If we reach here, the test passes
        $this->assertTrue(true);
    }

    public function test_publish_event_with_empty_payload()
    {
        $this->mock->shouldReceive('publish')
            ->once()
            ->with('order.created', [], [])
            ->andReturn(true);

        $result = EventMesh::publish('order.created', []);
        
        $this->assertTrue($result);
    }

    public function test_publish_event_with_complex_payload()
    {
        $payload = [
            'order_id' => 123,
            'items' => [
                ['id' => 1, 'quantity' => 2],
                ['id' => 2, 'quantity' => 1]
            ],
            'customer' => [
                'id' => 456,
                'name' => 'John Doe'
            ]
        ];

        $this->mock->shouldReceive('publish')
            ->once()
            ->with('order.created', $payload, [])
            ->andReturn(true);

        $result = EventMesh::publish('order.created', $payload);
        
        $this->assertTrue($result);
    }

    public function test_subscribe_with_invalid_callback()
    {
        $this->expectException(\TypeError::class);
        
        EventMesh::subscribe('order.created', 'not_a_callable');
    }

    public function test_subscribe_all_with_invalid_callback()
    {
        $this->expectException(\TypeError::class);
        
        EventMesh::subscribeAll('not_a_callable');
    }

    public function test_subscribe_all_with_invalid_filter_pattern()
    {
        $callback = function ($topic, $payload, $headers) {};
        
        $this->mock->shouldReceive('subscribeAll')
            ->once()
            ->with($callback, 'invalid[pattern')
            ->andReturn(false);

        $result = EventMesh::subscribeAll($callback, 'invalid[pattern');
        
        $this->assertFalse($result);
    }

    public function test_get_driver_with_invalid_name()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver: invalid');
        
        EventMesh::driver('invalid');
    }
} 