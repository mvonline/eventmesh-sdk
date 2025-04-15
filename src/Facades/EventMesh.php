<?php

namespace EventMesh\LaravelSdk\Facades;

use EventMesh\LaravelSdk\EventMeshManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool publish(string $topic, array $payload, array $headers = [])
 * @method static bool subscribe(string $topic, callable $callback)
 * @method static EventMeshManager driver(?string $name = null)
 * @method static void disconnect()
 *
 * @see \EventMesh\LaravelSdk\EventMeshManager
 */
class EventMesh extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'eventmesh';
    }
} 