<?php

namespace EventMesh\LaravelSdk\Commands;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'eventmesh:publish 
        {topic : The topic to publish to}
        {--payload=* : The payload to publish (key=value format)}
        {--header=* : Additional headers (key=value format)}
        {--driver= : The driver to use (http, mqtt, grpc, cloudevents)}';

    protected $description = 'Publish an event to a topic';

    private EventMeshManager $eventMesh;

    public function __construct(EventMeshManager $eventMesh)
    {
        parent::__construct();
        $this->eventMesh = $eventMesh;
    }

    public function handle(): int
    {
        $topic = $this->argument('topic');
        $payload = $this->parseKeyValuePairs($this->option('payload'));
        $headers = $this->parseKeyValuePairs($this->option('header'));
        $driver = $this->option('driver');

        if ($driver) {
            $this->eventMesh->driver($driver);
        }

        $this->info("Publishing to topic: {$topic}");
        $this->info("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        if (!empty($headers)) {
            $this->info("Headers: " . json_encode($headers, JSON_PRETTY_PRINT));
        }

        if ($this->eventMesh->publish($topic, $payload, $headers)) {
            $this->info('Event published successfully');
            return 0;
        }

        $this->error('Failed to publish event');
        return 1;
    }

    private function parseKeyValuePairs(array $pairs): array
    {
        $result = [];

        foreach ($pairs as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $result[$key] = $value;
            }
        }

        return $result;
    }
} 