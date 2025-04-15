<?php

namespace EventMesh\LaravelSdk\Tests\Storage;

use EventMesh\LaravelSdk\Storage\MongoSagaStorage;
use MongoDB\Client;

class MongoSagaStorageTest extends StorageTestCase
{
    private Client $client;
    private string $database = 'eventmesh_test';
    private string $collection = 'saga_logs_test';
    
    protected function setUp(): void
    {
        // Set up MongoDB connection
        $this->client = new Client('mongodb://localhost:27017');
        
        // Drop the test collection if it exists
        $this->client->{$this->database}->{$this->collection}->drop();
        
        parent::setUp();
    }
    
    protected function createStorage(): MongoSagaStorage
    {
        return new MongoSagaStorage(
            'mongodb://localhost:27017',
            $this->database,
            $this->collection
        );
    }
    
    protected function tearDown(): void
    {
        // Clean up the test collection
        $this->client->{$this->database}->{$this->collection}->drop();
        
        parent::tearDown();
    }
} 