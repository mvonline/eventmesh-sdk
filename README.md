# Laravel EventMesh SDK

A Laravel SDK for Apache EventMesh integration with multi-protocol support and Saga orchestration.

## Features

- Multi-protocol support:
  - HTTP (default)
  - MQTT (via php-mqtt/client)
  - gRPC (optional, via PHP gRPC)
  - CloudEvents (via custom PSR-compliant wrapper)
- Laravel 10+ integration
- Saga orchestration with compensation handlers
- Outbox pattern support
- Artisan commands for event publishing and listening
- Webhook support for HTTP events
- Comprehensive logging and monitoring
- Multiple storage backends for saga logs:
  - SQL (default)
  - MongoDB
  - Cassandra

## Requirements

- PHP 8.1+
- Laravel 10.0+
- Apache EventMesh server
- For MQTT: php-mqtt/client
- For gRPC: PHP gRPC extension and protobuf
- For MongoDB: mongodb/mongodb
- For Cassandra: datastax/php-driver

## Installation

1. Install the package via Composer:

```bash
composer require eventmesh/laravel-sdk
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=eventmesh-config
```

3. Run the migrations:

```bash
php artisan migrate
```

## Configuration

The configuration file `config/eventmesh.php` contains all the settings for the package:

```php
return [
    'default' => env('EVENTMESH_DRIVER', 'http'),
    'auto_connect' => env('EVENTMESH_AUTO_CONNECT', true),
    
    'drivers' => [
        'http' => [
            'base_url' => env('EVENTMESH_HTTP_URL', 'http://localhost:10105'),
            'timeout' => env('EVENTMESH_HTTP_TIMEOUT', 30),
        ],
        
        'mqtt' => [
            'host' => env('EVENTMESH_MQTT_HOST', 'localhost'),
            'port' => env('EVENTMESH_MQTT_PORT', 1883),
            'username' => env('EVENTMESH_MQTT_USERNAME'),
            'password' => env('EVENTMESH_MQTT_PASSWORD'),
            'client_id' => env('EVENTMESH_MQTT_CLIENT_ID'),
            'keep_alive' => env('EVENTMESH_MQTT_KEEP_ALIVE', 60),
            'qos' => env('EVENTMESH_MQTT_QOS', 1),
        ],
        
        'grpc' => [
            'host' => env('EVENTMESH_GRPC_HOST', 'localhost:50051'),
            'keep_alive' => env('EVENTMESH_GRPC_KEEP_ALIVE', 60000),
            'timeout' => env('EVENTMESH_GRPC_TIMEOUT', 30000),
        ],
        
        'cloudevents' => [
            'source' => env('EVENTMESH_CLOUDEVENTS_SOURCE', 'laravel-application'),
            'underlying_driver' => env('EVENTMESH_CLOUDEVENTS_DRIVER', 'http'),
        ],
    ],
    
    'webhook' => [
        'path' => env('EVENTMESH_WEBHOOK_PATH', 'eventmesh/webhook'),
        'middleware' => ['api'],
    ],
    
    'saga' => [
        'retry_attempts' => env('EVENTMESH_SAGA_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('EVENTMESH_SAGA_RETRY_DELAY', 60),
        'compensation_handlers' => [
            // Example compensation handlers:
            // 'payment.failed' => 'App\\Events\\CompensateOrder',
            // 'inventory.failed' => 'App\\Events\\RefundPayment',
        ],
    ],
    
    'storage' => [
        'driver' => env('EVENTMESH_STORAGE_DRIVER', 'sql'),
        
        'sql' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => 'saga_logs',
        ],
        
        'mongodb' => [
            'connection_string' => env('MONGODB_CONNECTION_STRING', 'mongodb://localhost:27017'),
            'database' => env('MONGODB_DATABASE', 'eventmesh'),
            'collection' => env('MONGODB_COLLECTION', 'saga_logs'),
        ],
        
        'cassandra' => [
            'contact_points' => explode(',', env('CASSANDRA_CONTACT_POINTS', 'localhost')),
            'keyspace' => env('CASSANDRA_KEYSPACE', 'eventmesh'),
            'table' => env('CASSANDRA_TABLE', 'saga_logs'),
        ],
    ],
];
```

## Usage

### Publishing Events

Using the Facade:

```php
use EventMesh\LaravelSdk\Facades\EventMesh;

// Publish an event
EventMesh::publish('order.created', [
    'order_id' => 123,
    'amount' => 99.99,
]);

// Publish with headers
EventMesh::publish('order.created', [
    'order_id' => 123,
    'amount' => 99.99,
], [
    'X-Correlation-Id' => 'abc123',
]);
```

Using Artisan:

```bash
# Publish an event
php artisan eventmesh:publish order.created --payload=order_id=123 --payload=amount=99.99

# Publish with headers
php artisan eventmesh:publish order.created --payload=order_id=123 --header=X-Correlation-Id=abc123
```

### Listening to Events

Using the Facade:

```php
use EventMesh\LaravelSdk\Facades\EventMesh;

EventMesh::subscribe('order.created', function ($topic, $payload) {
    // Handle the event
    Log::info("Order created: {$payload['order_id']}");
});
```

Using Artisan:

```bash
# Listen for events
php artisan eventmesh:listen order.created

# Listen with timeout
php artisan eventmesh:listen order.created --timeout=60
```

### Saga Orchestration

```php
use EventMesh\LaravelSdk\Saga\SagaManager;

class OrderController
{
    private SagaManager $sagaManager;
    
    public function __construct(SagaManager $sagaManager)
    {
        $this->sagaManager = $sagaManager;
    }
    
    public function store(Request $request)
    {
        // Start a saga
        $sagaInstanceId = $this->sagaManager->start('order.created', [
            'order_id' => $request->order_id,
            'amount' => $request->amount,
        ]);
        
        return response()->json(['saga_instance_id' => $sagaInstanceId]);
    }
}
```

Check saga status:

```bash
php artisan eventmesh:saga-status {saga_instance_id}
```

### Event Listeners

Create event listeners for your events:

```php
use Illuminate\Support\Facades\Event;

Event::listen('eventmesh.order.created', function ($event) {
    $payload = $event['payload'];
    $headers = $event['headers'];
    $sagaInstanceId = $event['saga_instance_id'] ?? null;
    
    // Handle the event
});
```

### Compensation Handlers

Define compensation handlers in your config:

```php
'compensation_handlers' => [
    'payment.failed' => 'App\\Events\\CompensateOrder',
    'inventory.failed' => 'App\\Events\\RefundPayment',
],
```

Create the compensation event:

```php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompensateOrder
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public string $sagaInstanceId,
        public string $originalEvent
    ) {}
}
```

## Storage Backends

The package supports multiple storage backends for saga logs:

### SQL Storage (Default)

```env
EVENTMESH_STORAGE_DRIVER=sql
DB_CONNECTION=mysql
```

The SQL storage implementation uses Laravel's database abstraction layer to store saga logs in a relational database.

### MongoDB Storage

```env
EVENTMESH_STORAGE_DRIVER=mongodb
MONGODB_CONNECTION_STRING=mongodb://localhost:27017
MONGODB_DATABASE=eventmesh
MONGODB_COLLECTION=saga_logs
```

The MongoDB storage implementation provides a NoSQL solution for storing saga logs, offering better scalability for high-throughput scenarios.

### Cassandra Storage

```env
EVENTMESH_STORAGE_DRIVER=cassandra
CASSANDRA_CONTACT_POINTS=localhost
CASSANDRA_KEYSPACE=eventmesh
CASSANDRA_TABLE=saga_logs
```

The Cassandra storage implementation offers a distributed, highly available storage solution for saga logs, ideal for large-scale distributed systems.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 