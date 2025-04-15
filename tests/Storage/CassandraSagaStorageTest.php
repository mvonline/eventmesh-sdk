<?php

namespace EventMesh\LaravelSdk\Tests\Storage;

use EventMesh\LaravelSdk\Storage\CassandraSagaStorage;
use Cassandra\Cluster;
use Cassandra\Session;

class CassandraSagaStorageTest extends StorageTestCase
{
    private Session $session;
    private string $keyspace = 'eventmesh_test';
    private string $table = 'saga_logs_test';
    
    protected function setUp(): void
    {
        // Set up Cassandra connection
        $cluster = (new Cluster())
            ->withContactPoints('localhost')
            ->withDefaultConsistency(\Cassandra::CONSISTENCY_LOCAL_QUORUM)
            ->build();
        
        $this->session = $cluster->connect();
        
        // Drop the test keyspace if it exists
        try {
            $this->session->execute(new \Cassandra\SimpleStatement("DROP KEYSPACE IF EXISTS {$this->keyspace}"));
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        parent::setUp();
    }
    
    protected function createStorage(): CassandraSagaStorage
    {
        return new CassandraSagaStorage(
            ['localhost'],
            $this->keyspace,
            $this->table
        );
    }
    
    protected function tearDown(): void
    {
        // Clean up the test keyspace
        try {
            $this->session->execute(new \Cassandra\SimpleStatement("DROP KEYSPACE IF EXISTS {$this->keyspace}"));
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        parent::tearDown();
    }
} 