<?php

namespace EventMesh\LaravelSdk\Commands;

use EventMesh\LaravelSdk\Saga\SagaManager;
use Illuminate\Console\Command;

class SagaStatusCommand extends Command
{
    protected $signature = 'eventmesh:saga-status {saga_instance_id?}';
    protected $description = 'Check the status of a saga instance';

    private SagaManager $sagaManager;

    public function __construct(SagaManager $sagaManager)
    {
        parent::__construct();
        $this->sagaManager = $sagaManager;
    }

    public function handle(): int
    {
        $sagaInstanceId = $this->argument('saga_instance_id');

        if (!$sagaInstanceId) {
            $this->error('Please provide a saga instance ID');
            return 1;
        }

        $status = $this->sagaManager->getSagaStatus($sagaInstanceId);

        $this->info("Saga Instance: {$status['saga_instance_id']}");
        $this->info("Overall Status: {$status['status']}");
        $this->newLine();

        $this->table(
            ['Event', 'Status', 'Retries', 'Error', 'Processed At'],
            collect($status['steps'])->map(function ($step) {
                return [
                    $step['event_name'],
                    $this->formatStatus($step['status']),
                    $step['retry_count'],
                    $step['error_message'] ?? '-',
                    $step['processed_at'] ?? '-',
                ];
            })->toArray()
        );

        return 0;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'pending' => '<fg=yellow>pending</>',
            'success' => '<fg=green>success</>',
            'failed' => '<fg=red>failed</>',
            default => $status,
        };
    }
} 