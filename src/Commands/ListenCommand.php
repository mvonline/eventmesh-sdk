<?php

namespace EventMesh\LaravelSdk\Commands;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Console\Command;

class ListenCommand extends Command
{
    protected $signature = 'eventmesh:listen 
        {topic : The topic to listen to}
        {--driver= : The driver to use (http, mqtt, grpc, cloudevents)}
        {--timeout=0 : Timeout in seconds (0 for infinite)}';

    protected $description = 'Listen for events on a topic';

    private EventMeshManager $eventMesh;
    private bool $shouldStop = false;

    public function __construct(EventMeshManager $eventMesh)
    {
        parent::__construct();
        $this->eventMesh = $eventMesh;
    }

    public function handle(): int
    {
        $topic = $this->argument('topic');
        $driver = $this->option('driver');
        $timeout = (int) $this->option('timeout');

        if ($driver) {
            $this->eventMesh->driver($driver);
        }

        $this->info("Listening for events on topic: {$topic}");
        $this->info('Press Ctrl+C to stop');

        // Register signal handler for graceful shutdown
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function () {
                $this->shouldStop = true;
            });
        }

        $startTime = time();

        $this->eventMesh->subscribe($topic, function ($topic, $payload) {
            $this->info("\nReceived event on topic: {$topic}");
            $this->info("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        });

        while (!$this->shouldStop) {
            if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                $this->info("\nTimeout reached, stopping listener");
                break;
            }

            if (extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }

            usleep(100000); // Sleep for 100ms
        }

        $this->eventMesh->disconnect();
        $this->info("\nStopped listening");

        return 0;
    }
} 