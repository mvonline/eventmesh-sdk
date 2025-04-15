<?php

namespace EventMesh\LaravelSdk\Drivers;

use EventMesh\LaravelSdk\Contracts\EventMeshDriverInterface;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttDriver implements EventMeshDriverInterface
{
    private ?MqttClient $client = null;
    private array $config;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function publish(string $topic, array $payload, array $headers = []): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->connect();
            }

            $this->client->publish(
                $topic,
                json_encode($payload),
                $this->config['qos'] ?? 1
            );

            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh MQTT publish failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->connect();
            }

            $this->client->subscribe($topic, function ($topic, $message) use ($callback) {
                $payload = json_decode($message, true);
                $callback($topic, $payload);
            }, $this->config['qos'] ?? 1);

            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh MQTT subscribe failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function getName(): string
    {
        return 'mqtt';
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->client !== null;
    }

    public function connect(): bool
    {
        try {
            $connectionSettings = (new ConnectionSettings)
                ->setUsername($this->config['username'] ?? null)
                ->setPassword($this->config['password'] ?? null)
                ->setKeepAliveInterval($this->config['keep_alive'] ?? 60)
                ->setConnectTimeout($this->config['timeout'] ?? 3)
                ->setReconnectAutomatically(true);

            $this->client = new MqttClient(
                $this->config['host'] ?? 'localhost',
                $this->config['port'] ?? 1883,
                $this->config['client_id'] ?? uniqid('eventmesh_', true)
            );

            $this->client->connect($connectionSettings);
            $this->connected = true;

            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh MQTT connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function disconnect(): bool
    {
        try {
            if ($this->isConnected()) {
                $this->client->disconnect();
            }
            $this->connected = false;
            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh MQTT disconnect failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
} 