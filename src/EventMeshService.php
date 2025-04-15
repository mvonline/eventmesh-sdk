<?php

namespace EventMesh\LaravelSdk;

use EventMesh\LaravelSdk\Drivers\EventMeshDriverInterface;
use Illuminate\Support\Facades\Log;

class EventMeshService
{
    private array $drivers = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function registerDriver(EventMeshDriverInterface $driver): void
    {
        $driver->initialize($this->config);
        $this->drivers[$driver->getName()] = $driver;
    }

    public function publish(string $topic, array $payload, array $headers = [], ?string $driver = null): bool
    {
        $driver = $driver ?? $this->config['default_driver'];
        
        if (!isset($this->drivers[$driver])) {
            Log::error('EventMesh driver not found', ['driver' => $driver]);
            return false;
        }

        return $this->drivers[$driver]->publish($topic, $payload, $headers);
    }

    public function subscribe(string $topic, callable $callback, ?string $driver = null): bool
    {
        $driver = $driver ?? $this->config['default_driver'];
        
        if (!isset($this->drivers[$driver])) {
            Log::error('EventMesh driver not found', ['driver' => $driver]);
            return false;
        }

        return $this->drivers[$driver]->subscribe($topic, $callback);
    }

    public function getDriver(?string $name = null): ?EventMeshDriverInterface
    {
        $name = $name ?? $this->config['default_driver'];
        return $this->drivers[$name] ?? null;
    }
} 