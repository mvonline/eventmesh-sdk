<?php

namespace EventMesh\LaravelSdk\Drivers;

use EventMesh\LaravelSdk\Contracts\EventMeshDriverInterface;
use Illuminate\Support\Facades\Log;

class GrpcDriver implements EventMeshDriverInterface
{
    private array $config;
    private bool $connected = false;
    private $client;

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

            // Create gRPC request
            $request = new \EventMesh\Proto\PublishRequest([
                'topic' => $topic,
                'payload' => json_encode($payload),
                'headers' => $headers,
            ]);

            // Send request
            list($response, $status) = $this->client->Publish($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception($status->details);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh gRPC publish failed', [
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

            // Create subscription request
            $request = new \EventMesh\Proto\SubscribeRequest([
                'topic' => $topic,
            ]);

            // Start streaming call
            $call = $this->client->Subscribe($request);
            
            // Handle incoming messages
            while ($response = $call->read()) {
                $payload = json_decode($response->getPayload(), true);
                $callback($topic, $payload);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh gRPC subscribe failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
            ]);
            return false;
        }
    }

    public function getName(): string
    {
        return 'grpc';
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function connect(): bool
    {
        try {
            // Load the generated gRPC client
            $this->client = new \EventMesh\Proto\EventMeshServiceClient(
                $this->config['host'] ?? 'localhost:50051',
                [
                    'credentials' => \Grpc\ChannelCredentials::createInsecure(),
                    'grpc.keepalive_time_ms' => $this->config['keep_alive'] ?? 60000,
                    'grpc.keepalive_timeout_ms' => $this->config['timeout'] ?? 30000,
                ]
            );

            // Test connection with health check
            $request = new \EventMesh\Proto\HealthCheckRequest();
            list($response, $status) = $this->client->HealthCheck($request)->wait();

            if ($status->code !== \Grpc\STATUS_OK) {
                throw new \Exception($status->details);
            }

            $this->connected = true;
            return true;
        } catch (\Exception $e) {
            Log::error('EventMesh gRPC connection failed', [
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