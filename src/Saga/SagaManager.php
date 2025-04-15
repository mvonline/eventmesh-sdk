<?php

namespace EventMesh\LaravelSdk\Saga;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SagaManager
{
    private EventMeshManager $eventMesh;
    private array $config;

    public function __construct(EventMeshManager $eventMesh, array $config)
    {
        $this->eventMesh = $eventMesh;
        $this->config = $config;
    }

    public function start(string $eventName, array $payload, array $headers = []): string
    {
        $sagaInstanceId = Str::uuid()->toString();

        DB::table('saga_logs')->insert([
            'saga_instance_id' => $sagaInstanceId,
            'event_name' => $eventName,
            'status' => 'pending',
            'payload' => json_encode($payload),
            'headers' => json_encode($headers),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->eventMesh->publish($eventName, $payload, array_merge($headers, [
            'X-Saga-Instance-Id' => $sagaInstanceId,
        ]));

        return $sagaInstanceId;
    }

    public function handleEvent(string $sagaInstanceId, string $eventName, array $payload, array $headers = []): void
    {
        DB::transaction(function () use ($sagaInstanceId, $eventName, $payload, $headers) {
            $log = DB::table('saga_logs')
                ->where('saga_instance_id', $sagaInstanceId)
                ->where('event_name', $eventName)
                ->first();

            if (!$log) {
                DB::table('saga_logs')->insert([
                    'saga_instance_id' => $sagaInstanceId,
                    'event_name' => $eventName,
                    'status' => 'pending',
                    'payload' => json_encode($payload),
                    'headers' => json_encode($headers),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            try {
                // Process the event
                event("eventmesh.{$eventName}", [
                    'payload' => $payload,
                    'headers' => $headers,
                    'saga_instance_id' => $sagaInstanceId,
                ]);

                // Update log status
                DB::table('saga_logs')
                    ->where('saga_instance_id', $sagaInstanceId)
                    ->where('event_name', $eventName)
                    ->update([
                        'status' => 'success',
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);

            } catch (\Exception $e) {
                Log::error('Saga event processing failed', [
                    'saga_instance_id' => $sagaInstanceId,
                    'event_name' => $eventName,
                    'error' => $e->getMessage(),
                ]);

                // Update log status and increment retry count
                DB::table('saga_logs')
                    ->where('saga_instance_id', $sagaInstanceId)
                    ->where('event_name', $eventName)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'retry_count' => DB::raw('retry_count + 1'),
                        'updated_at' => now(),
                    ]);

                // Check if we should trigger compensation
                if ($this->shouldTriggerCompensation($sagaInstanceId, $eventName)) {
                    $this->triggerCompensation($sagaInstanceId, $eventName);
                }
            }
        });
    }

    private function shouldTriggerCompensation(string $sagaInstanceId, string $eventName): bool
    {
        $log = DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->where('event_name', $eventName)
            ->first();

        return $log && 
               $log->retry_count >= ($this->config['retry_attempts'] ?? 3);
    }

    private function triggerCompensation(string $sagaInstanceId, string $eventName): void
    {
        $compensationHandler = $this->config['compensation_handlers'][$eventName] ?? null;

        if ($compensationHandler) {
            try {
                event($compensationHandler, [
                    'saga_instance_id' => $sagaInstanceId,
                    'original_event' => $eventName,
                ]);
            } catch (\Exception $e) {
                Log::error('Compensation handler failed', [
                    'saga_instance_id' => $sagaInstanceId,
                    'event_name' => $eventName,
                    'compensation_handler' => $compensationHandler,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function getSagaStatus(string $sagaInstanceId): array
    {
        $logs = DB::table('saga_logs')
            ->where('saga_instance_id', $sagaInstanceId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'event_name' => $log->event_name,
                    'status' => $log->status,
                    'retry_count' => $log->retry_count,
                    'error_message' => $log->error_message,
                    'processed_at' => $log->processed_at,
                ];
            })
            ->toArray();

        return [
            'saga_instance_id' => $sagaInstanceId,
            'steps' => $logs,
            'status' => $this->determineOverallStatus($logs),
        ];
    }

    private function determineOverallStatus(array $logs): string
    {
        if (empty($logs)) {
            return 'unknown';
        }

        $hasFailed = collect($logs)->contains('status', 'failed');
        $hasPending = collect($logs)->contains('status', 'pending');
        $allSuccess = collect($logs)->every('status', 'success');

        if ($hasFailed) {
            return 'failed';
        }

        if ($hasPending) {
            return 'pending';
        }

        if ($allSuccess) {
            return 'completed';
        }

        return 'unknown';
    }
} 