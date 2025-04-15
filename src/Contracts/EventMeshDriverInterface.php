<?php

namespace EventMesh\LaravelSdk\Contracts;

interface EventMeshDriverInterface
{
    /**
     * Publish an event to a specific topic
     *
     * @param string $topic
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    public function publish(string $topic, array $payload, array $headers = []): bool;

    /**
     * Subscribe to a topic
     *
     * @param string $topic
     * @param callable $callback
     * @return bool
     */
    public function subscribe(string $topic, callable $callback): bool;

    /**
     * Get the driver name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the driver is connected
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Connect to the event mesh server
     *
     * @return bool
     */
    public function connect(): bool;

    /**
     * Disconnect from the event mesh server
     *
     * @return bool
     */
    public function disconnect(): bool;
} 