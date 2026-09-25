<?php

namespace Asynit\Runner;

use Revolt\EventLoop\Suspension;

/**
 * Keeps the per test state PHPUnit holds in process wide statics consistent while tests interleave: whatever
 * a test's fiber saw when it suspended is what it finds when it resumes.
 *
 * @template T
 *
 * @implements Suspension<T>
 *
 * @internal
 */
final class FiberSwitchSuspension implements Suspension
{
    /**
     * @param Suspension<T> $suspension
     */
    public function __construct(private readonly Suspension $suspension)
    {
    }

    public function resume(mixed $value = null): void
    {
        $this->suspension->resume($value);
    }

    public function suspend(): mixed
    {
        $assertionCounter = AssertionCounter::current();
        $assertionCounter?->suspended();
        $singletonState = SingletonState::save();

        try {
            return $this->suspension->suspend();
        } finally {
            SingletonState::restore($singletonState);
            $assertionCounter?->resumed();
        }
    }

    public function throw(\Throwable $throwable): void
    {
        $this->suspension->throw($throwable);
    }
}
