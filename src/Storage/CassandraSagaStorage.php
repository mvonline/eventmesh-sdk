<?php

namespace EventMesh\LaravelSdk\Storage;

use EventMesh\LaravelSdk\Contracts\SagaStorageInterface;
use Cassandra\Cluster;
use Cassandra\Session;
use Cassandra\SimpleStatement;

class CassandraSagaStorage implements SagaStorageInterface
{
    private Session $session;
    private string $keyspace;
    private string $table;

    public function __construct(array $contactPoints, string $keyspace = 'eventmesh', string $table = 'saga_logs')
    {
        $cluster = (new Cluster())
            ->withContactPoints(...$contactPoints)
            ->withDefaultConsistency(\Cassandra::CONSISTENCY_LOCAL_QUORUM)
            ->build();

        $this->session = $cluster->connect();
        $this->keyspace = $keyspace;
        $this->table = $table;

        $this->initializeSchema();
    }

    private function initializeSchema(): void
    {
        // Create keyspace if not exists
        $this->session->execute(new SimpleStatement(
            "CREATE KEYSPACE IF NOT EXISTS {$this->keyspace} 
             WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1}"
        ));

        // Create table if not exists
        $this->session->execute(new SimpleStatement(
            "CREATE TABLE IF NOT EXISTS {$this->keyspace}.{$this->table} (
                saga_instance_id text,
                event_name text,
                status text,
                payload text,
                headers text,
                retry_count int,
                error_message text,
                processed_at timestamp,
                created_at timestamp,
                updated_at timestamp,
                PRIMARY KEY ((saga_instance_id), event_name)
            )"
        ));
    }

    public function store(array $data): bool
    {
        try {
            $statement = $this->session->prepare(
                "INSERT INTO {$this->keyspace}.{$this->table} 
                (saga_instance_id, event_name, status, payload, headers, retry_count, 
                error_message, processed_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $this->session->execute($statement, [
                $data['saga_instance_id'],
                $data['event_name'],
                $data['status'] ?? 'pending',
                json_encode($data['payload'] ?? []),
                json_encode($data['headers'] ?? []),
                $data['retry_count'] ?? 0,
                $data['error_message'] ?? null,
                $data['processed_at'] ?? null,
                $data['created_at'] ?? new \DateTime(),
                $data['updated_at'] ?? new \DateTime()
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update(string $sagaInstanceId, string $eventName, array $data): bool
    {
        try {
            $updates = [];
            $values = [];
            $types = [];

            foreach ($data as $key => $value) {
                if ($key === 'payload' || $key === 'headers') {
                    $value = json_encode($value);
                }
                $updates[] = "{$key} = ?";
                $values[] = $value;
                $types[] = is_null($value) ? 'null' : '?';
            }

            $values[] = $sagaInstanceId;
            $values[] = $eventName;

            $statement = $this->session->prepare(
                "UPDATE {$this->keyspace}.{$this->table} 
                SET " . implode(', ', $updates) . " 
                WHERE saga_instance_id = ? AND event_name = ?"
            );

            $this->session->execute($statement, $values);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLogs(string $sagaInstanceId): array
    {
        try {
            $statement = $this->session->prepare(
                "SELECT * FROM {$this->keyspace}.{$this->table} 
                WHERE saga_instance_id = ? ORDER BY created_at ASC"
            );

            $rows = $this->session->execute($statement, [$sagaInstanceId]);
            $logs = [];

            foreach ($rows as $row) {
                $log = (array) $row;
                $log['payload'] = json_decode($log['payload'], true);
                $log['headers'] = json_decode($log['headers'], true);
                $logs[] = $log;
            }

            return $logs;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getLog(string $sagaInstanceId, string $eventName): ?array
    {
        try {
            $statement = $this->session->prepare(
                "SELECT * FROM {$this->keyspace}.{$this->table} 
                WHERE saga_instance_id = ? AND event_name = ?"
            );

            $row = $this->session->execute($statement, [$sagaInstanceId, $eventName])->first();
            
            if (!$row) {
                return null;
            }

            $log = (array) $row;
            $log['payload'] = json_decode($log['payload'], true);
            $log['headers'] = json_decode($log['headers'], true);
            
            return $log;
        } catch (\Exception $e) {
            return null;
        }
    }
} 