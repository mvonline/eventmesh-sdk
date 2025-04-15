<?php

namespace App\Contracts;

/**
 * Interface DriverInterface
 *
 * @package App\Contracts
 */
interface DriverInterface
{
    /**
     * Subscribe to all events with an optional filter pattern.
     *
     * @param callable $callback
     * @param string|null $filterPattern
     * @return void
     */
    public function subscribeAll(callable $callback, ?string $filterPattern = null): void;
} 