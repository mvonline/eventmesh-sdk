<?php

namespace EventMesh\LaravelSdk\Console\Commands;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListenAllEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eventmesh:listen-all 
                            {--timeout=0 : Timeout in seconds (0 for no timeout)}
                            {--filter= : Filter events by pattern (e.g., "order.*")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to all events from EventMesh';

    /**
     * The EventMesh manager instance.
     *
     * @var EventMeshManager
     */
    protected $eventMesh;

    /**
     * Create a new command instance.
     *
     * @param EventMeshManager $eventMesh
     * @return void
     */
    public function __construct(EventMeshManager $eventMesh)
    {
        parent::__construct();
        $this->eventMesh = $eventMesh;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        $filter = $this->option('filter');

        $this->info('Starting to listen to all events...');
        if ($filter) {
            $this->info("Filter: {$filter}");
        }
        if ($timeout > 0) {
            $this->info("Timeout: {$timeout} seconds");
        }

        // Subscribe to all events
        $this->eventMesh->subscribeAll(function ($topic, $payload, $headers) {
            $this->handleEvent($topic, $payload, $headers);
        }, $filter);

        // If timeout is set, wait for the specified time
        if ($timeout > 0) {
            $this->info("Listening for {$timeout} seconds...");
            sleep($timeout);
            $this->info('Timeout reached. Stopping listener.');
            return 0;
        }

        // Otherwise, keep listening indefinitely
        $this->info('Listening indefinitely. Press Ctrl+C to stop.');
        while (true) {
            sleep(1);
        }

        return 0;
    }

    /**
     * Handle an incoming event.
     *
     * @param string $topic
     * @param array $payload
     * @param array $headers
     * @return void
     */
    protected function handleEvent(string $topic, array $payload, array $headers)
    {
        $this->info("Received event: {$topic}");
        $this->line("Payload: " . json_encode($payload));
        $this->line("Headers: " . json_encode($headers));
        $this->line('----------------------------------------');

        // Log the event
        Log::info("EventMesh event received", [
            'topic' => $topic,
            'payload' => $payload,
            'headers' => $headers,
        ]);

        // Dispatch a Laravel event
        event("eventmesh.{$topic}", [
            'payload' => $payload,
            'headers' => $headers,
            'saga_instance_id' => $headers['X-Saga-Instance-Id'] ?? null,
        ]);
    }
} 