<?php

namespace EventMesh\LaravelSdk\Storage;

use EventMesh\LaravelSdk\Contracts\SagaStorageInterface;
use MongoDB\Client;

class MongoSagaStorage implements SagaStorageInterface
{
    private Client $client;
    private string $database;
    private string $collection;

    public function __construct(string $connectionString, string $database = 'eventmesh', string $collection = 'saga_logs')
    {
        $this->client = new Client($connectionString);
        $this->database = $database;
        $this->collection = $collection;
    }

    public function store(array $data): bool
    {
        try {
            $this->client->{$this->database}->{$this->collection}->insertOne($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update(string $sagaInstanceId, string $eventName, array $data): bool
    {
        try {
            $result = $this->client->{$this->database}->{$this->collection}->updateOne(
                [
                    'saga_instance_id' => $sagaInstanceId,
                    'event_name' => $eventName
                ],
                ['$set' => $data]
            );
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLogs(string $sagaInstanceId): array
    {
        try {
            $cursor = $this->client->{$this->database}->{$this->collection}->find(
                ['saga_instance_id' => $sagaInstanceId],
                ['sort' => ['created_at' => 1]]
            );
            return iterator_to_array($cursor);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getLog(string $sagaInstanceId, string $eventName): ?array
    {
        try {
            $document = $this->client->{$this->database}->{$this->collection}->findOne([
                'saga_instance_id' => $sagaInstanceId,
                'event_name' => $eventName
            ]);
            return $document ? (array) $document : null;
        } catch (\Exception $e) {
            return null;
        }
    }
} 