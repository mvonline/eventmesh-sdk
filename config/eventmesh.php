<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EventMesh Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the EventMesh package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the storage backend for saga logs.
    | Supported drivers: sql, mongodb, cassandra
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default driver that will be used by the EventMesh
    | package. Supported drivers are: "http", "mqtt", "grpc", and "cloudevents".
    |
    */
    'default' => env('EVENTMESH_DRIVER', 'http'),

    /*
    |--------------------------------------------------------------------------
    | Auto Connect
    |--------------------------------------------------------------------------
    |
    | This option determines if the driver should automatically connect when
    | initialized. Set this to false if you want to manually control the
    | connection lifecycle.
    |
    */
    'auto_connect' => env('EVENTMESH_AUTO_CONNECT', true),

    /*
    |--------------------------------------------------------------------------
    | Drivers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each supported driver. Each driver
    | has its own configuration section with specific options.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | This section configures the webhook endpoint for receiving events when
    | using the HTTP driver. The webhook URL will be automatically registered
    | as a route in your Laravel application.
    |
    */
    'webhook' => [
        'path' => env('EVENTMESH_WEBHOOK_PATH', 'eventmesh/webhook'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Saga Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the saga behavior and retry settings.
    |
    */
    'saga' => [
        'retry_attempts' => env('EVENTMESH_SAGA_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('EVENTMESH_SAGA_RETRY_DELAY', 60), // in seconds
        'compensation_handlers' => [
            // Example compensation handlers:
            // 'payment.failed' => 'App\\Events\\CompensateOrder',
            // 'inventory.failed' => 'App\\Events\\RefundPayment',
        ],
    ],
]; 