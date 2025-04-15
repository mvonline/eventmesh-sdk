<?php

namespace EventMesh\LaravelSdk;

use EventMesh\LaravelSdk\Contracts\EventMeshDriverInterface;
use EventMesh\LaravelSdk\Drivers\CloudEventsDriver;
use EventMesh\LaravelSdk\Drivers\GrpcDriver;
use EventMesh\LaravelSdk\Drivers\HttpDriver;
use EventMesh\LaravelSdk\Drivers\MqttDriver;
use Illuminate\Support\Facades\Log;

class EventMeshManager
{
    private array $config;
    private ?EventMeshDriverInterface $driver = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function driver(?string $name = null): EventMeshDriverInterface
    {
        $name = $name ?? $this->config['default'] ?? 'http';

        if ($this->driver && $this->driver->getName() === $name) {
            return $this->driver;
        }

        $this->driver = $this->createDriver($name);
        return $this->driver;
    }

    private function createDriver(string $name): EventMeshDriverInterface
    {
        $driverConfig = $this->config['drivers'][$name] ?? [];

        $driver = match ($name) {
            'http' => new HttpDriver($driverConfig),
            'mqtt' => new MqttDriver($driverConfig),
            'grpc' => new GrpcDriver($driverConfig),
            'cloudevents' => new CloudEventsDriver(
                $this->createDriver($driverConfig['underlying_driver'] ?? 'http'),
                $driverConfig
            ),
            default => throw new \InvalidArgumentException("Unsupported driver: {$name}"),
        };

        if ($this->config['auto_connect'] ?? true) {
            $driver->connect();
        }

        return $driver;
    }

    public function publish(string $topic, array $payload, array $headers = []): bool
    {
        try {
            return $this->driver()->publish($topic, $payload, $headers);
        } catch (\Exception $e) {
            Log::error('EventMesh publish failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): bool
    {
        try {
            return $this->driver()->subscribe($topic, $callback);
        } catch (\Exception $e) {
            Log::error('EventMesh subscribe failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->driver) {
            $this->driver->disconnect();
        }
    }
} 