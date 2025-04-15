<?php

namespace EventMesh\LaravelSdk\Drivers;

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
     * Initialize the driver with configuration
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void;

    /**
     * Get the driver name
     *
     * @return string
     */
    public function getName(): string;
} 