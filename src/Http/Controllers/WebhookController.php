<?php

namespace EventMesh\LaravelSdk\Http\Controllers;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    private EventMeshManager $eventMesh;

    public function __construct(EventMeshManager $eventMesh)
    {
        $this->eventMesh = $eventMesh;
    }

    public function handle(Request $request): Response
    {
        try {
            $topic = $request->header('X-EventMesh-Topic');
            $payload = $request->all();
            $headers = $request->headers->all();

            if (!$topic) {
                Log::error('EventMesh webhook received without topic', [
                    'headers' => $headers,
                    'payload' => $payload,
                ]);
                return response('Missing topic header', Response::HTTP_BAD_REQUEST);
            }

            // Dispatch a Laravel event that can be listened to
            event("eventmesh.{$topic}", [
                'payload' => $payload,
                'headers' => $headers,
            ]);

            return response('Event received', Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('EventMesh webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal server error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 