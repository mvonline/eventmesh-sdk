<?php

namespace EventMesh\LaravelSdk\Drivers;

use EventMesh\LaravelSdk\Contracts\EventMeshDriverInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HttpDriver implements EventMeshDriverInterface
{
    private Client $client;
    private string $baseUrl;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? 'http://localhost:10105', '/');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function publish(string $topic, array $payload, array $headers = []): bool
    {
        try {
            $response = $this->client->post('/api/v1/publish', [
                'json' => [
                    'topic' => $topic,
                    'payload' => $payload,
                    'headers' => $headers,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('EventMesh HTTP publish failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): bool
    {
        // HTTP driver doesn't support real-time subscriptions
        // Instead, it will be handled by the webhook endpoint
        return true;
    }

    public function getName(): string
    {
        return 'http';
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function connect(): bool
    {
        try {
            $response = $this->client->get('/api/v1/health');
            $this->connected = $response->getStatusCode() === 200;
            return $this->connected;
        } catch (GuzzleException $e) {
            Log::error('EventMesh HTTP connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function disconnect(): bool
    {
        $this->connected = false;
        return true;
    }
} 