<?php

namespace EventMesh\LaravelSdk\Drivers;

use EventMesh\LaravelSdk\Contracts\EventMeshDriverInterface;
use Illuminate\Support\Facades\Log;

class CloudEventsDriver implements EventMeshDriverInterface
{
    private EventMeshDriverInterface $driver;
    private array $config;
    private bool $connected = false;

    public function __construct(EventMeshDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function publish(string $topic, array $payload, array $headers = []): bool
    {
        try {
            $cloudEvent = [
                'specversion' => '1.0',
                'type' => $topic,
                'source' => $this->config['source'] ?? 'laravel-application',
                'id' => uniqid('evt_', true),
                'time' => date('c'),
                'datacontenttype' => 'application/json',
                'data' => $payload,
            ];

            // Merge any additional CloudEvents attributes from headers
            $cloudEvent = array_merge($cloudEvent, array_filter($headers, function ($key) {
                return !in_array($key, ['specversion', 'type', 'source', 'id', 'time', 'datacontenttype', 'data']);
            }, ARRAY_FILTER_USE_KEY));

            return $this->driver->publish($topic, $cloudEvent, $headers);
        } catch (\Exception $e) {
            Log::error('EventMesh CloudEvents publish failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): bool
    {
        try {
            return $this->driver->subscribe($topic, function ($topic, $cloudEvent) use ($callback) {
                // Validate CloudEvents format
                if (!$this->isValidCloudEvent($cloudEvent)) {
                    Log::error('Invalid CloudEvents format received', [
                        'topic' => $topic,
                        'event' => $cloudEvent,
                    ]);
                    return;
                }

                // Extract the actual payload from the CloudEvents data field
                $payload = $cloudEvent['data'] ?? [];
                $callback($topic, $payload);
            });
        } catch (\Exception $e) {
            Log::error('EventMesh CloudEvents subscribe failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function getName(): string
    {
        return 'cloudevents';
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->driver->isConnected();
    }

    public function connect(): bool
    {
        try {
            $this->connected = $this->driver->connect();
            return $this->connected;
        } catch (\Exception $e) {
            Log::error('EventMesh CloudEvents connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function disconnect(): bool
    {
        try {
            $this->connected = false;
            return $this->driver->disconnect();
        } catch (\Exception $e) {
            Log::error('EventMesh CloudEvents disconnect failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function isValidCloudEvent(array $event): bool
    {
        $requiredFields = ['specversion', 'type', 'source', 'id', 'time', 'datacontenttype', 'data'];
        
        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }

        return true;
    }
} 