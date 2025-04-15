<?php

namespace EventMesh\LaravelSdk\Contracts;

interface SagaStorageInterface
{
    /**
     * Store a saga log entry
     *
     * @param array $data
     * @return bool
     */
    public function store(array $data): bool;

    /**
     * Update a saga log entry
     *
     * @param string $sagaInstanceId
     * @param string $eventName
     * @param array $data
     * @return bool
     */
    public function update(string $sagaInstanceId, string $eventName, array $data): bool;

    /**
     * Get saga logs for an instance
     *
     * @param string $sagaInstanceId
     * @return array
     */
    public function getLogs(string $sagaInstanceId): array;

    /**
     * Get a specific saga log entry
     *
     * @param string $sagaInstanceId
     * @param string $eventName
     * @return array|null
     */
    public function getLog(string $sagaInstanceId, string $eventName): ?array;
} 