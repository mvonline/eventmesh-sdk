<?php

namespace EventMesh\LaravelSdk\Storage;

use EventMesh\LaravelSdk\Contracts\SagaStorageInterface;
use Illuminate\Support\Facades\DB;

class SqlSagaStorage implements SagaStorageInterface
{
    public function store(array $data): bool
    {
        return DB::table('saga_logs')->insert($data);
    }

    public function update(string $sagaInstanceId, string $eventName, array $data): bool
    {
        return DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->where('event_name', $eventName)
            ->update($data);
    }

    public function getLogs(string $sagaInstanceId): array
    {
        return DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function getLog(string $sagaInstanceId, string $eventName): ?array
    {
        $log = DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->where('event_name', $eventName)
            ->first();

        return $log ? (array) $log : null;
    }
} 