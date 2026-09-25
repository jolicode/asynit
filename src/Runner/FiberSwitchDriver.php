<?php

namespace Asynit\Runner;

use Revolt\EventLoop\CallbackType;
use Revolt\EventLoop\Driver;
use Revolt\EventLoop\Suspension;

/**
 * Decorates the event loop driver so every fiber switch goes through FiberSwitchSuspension: amphp suspends
 * exclusively through EventLoop::getSuspension(), which makes it the one place to observe them.
 *
 * @internal
 */
final class FiberSwitchDriver implements Driver
{
    public function __construct(private readonly Driver $driver)
    {
    }

    public function run(): void
    {
        $this->driver->run();
    }

    public function stop(): void
    {
        $this->driver->stop();
    }

    /**
     * @return Suspension<mixed>
     */
    public function getSuspension(): Suspension
    {
        return new FiberSwitchSuspension($this->driver->getSuspension());
    }

    public function isRunning(): bool
    {
        return $this->driver->isRunning();
    }

    public function queue(\Closure $closure, mixed ...$args): void
    {
        $this->driver->queue($closure, ...$args);
    }

    public function defer(\Closure $closure): string
    {
        return $this->driver->defer($closure);
    }

    public function delay(float $delay, \Closure $closure): string
    {
        return $this->driver->delay($delay, $closure);
    }

    public function repeat(float $interval, \Closure $closure): string
    {
        return $this->driver->repeat($interval, $closure);
    }

    public function onReadable(mixed $stream, \Closure $closure): string
    {
        return $this->driver->onReadable($stream, $closure);
    }

    public function onWritable(mixed $stream, \Closure $closure): string
    {
        return $this->driver->onWritable($stream, $closure);
    }

    public function onSignal(int $signal, \Closure $closure): string
    {
        return $this->driver->onSignal($signal, $closure);
    }

    public function enable(string $callbackId): string
    {
        return $this->driver->enable($callbackId);
    }

    public function cancel(string $callbackId): void
    {
        $this->driver->cancel($callbackId);
    }

    public function disable(string $callbackId): string
    {
        return $this->driver->disable($callbackId);
    }

    public function reference(string $callbackId): string
    {
        return $this->driver->reference($callbackId);
    }

    public function unreference(string $callbackId): string
    {
        return $this->driver->unreference($callbackId);
    }

    public function setErrorHandler(?\Closure $errorHandler): void
    {
        $this->driver->setErrorHandler($errorHandler);
    }

    public function getErrorHandler(): ?\Closure
    {
        return $this->driver->getErrorHandler();
    }

    public function getHandle(): mixed
    {
        return $this->driver->getHandle();
    }

    public function getIdentifiers(): array
    {
        return $this->driver->getIdentifiers();
    }

    public function getType(string $callbackId): CallbackType
    {
        return $this->driver->getType($callbackId);
    }

    public function isEnabled(string $callbackId): bool
    {
        return $this->driver->isEnabled($callbackId);
    }

    public function isReferenced(string $callbackId): bool
    {
        return $this->driver->isReferenced($callbackId);
    }

    /**
     * @return array<mixed>
     */
    public function __debugInfo(): array
    {
        return $this->driver->__debugInfo();
    }
}
