<?php

namespace EventMesh\LaravelSdk\Tests\Storage;

use EventMesh\LaravelSdk\Storage\SqlSagaStorage;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class SqlSagaStorageTest extends StorageTestCase
{
    protected function setUp(): void
    {
        // Set up the database connection
        $this->app = $this->createApplication();
        
        // Create the saga_logs table
        $this->createSagaLogsTable();
        
        parent::setUp();
    }
    
    protected function createApplication()
    {
        $app = require __DIR__ . '/../../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
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
    
    protected function createStorage(): SqlSagaStorage
    {
        return new SqlSagaStorage();
    }
    
    protected function tearDown(): void
    {
        // Clean up the database
        DB::statement('DROP TABLE IF EXISTS saga_logs');
        
        parent::tearDown();
    }
} 