<?php

namespace Asynit\Runner;

use PHPUnit\Framework\Assert;

/**
 * Per test assertion count.
 *
 * Assert::$count is a process wide static, so reading it around a test also picks up whatever the tests running
 * alongside asserted meanwhile. It only moves while a fiber is running, though, so crediting a test with the
 * deltas measured between the moments its fiber is resumed and suspended gives its own count exactly.
 * FiberSwitchSuspension reports those moments.
 *
 * @internal
 */
final class AssertionCounter
{
    /** @var \WeakMap<\Fiber<mixed, mixed, mixed, mixed>, self>|null */
    private static ?\WeakMap $counters = null;

    private int $credited = 0;

    private int $since;

    private function __construct()
    {
        $this->since = Assert::getCount();
    }

    public static function start(): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return;
        }

        /* @phpstan-ignore-next-line */
        self::$counters ??= new \WeakMap();
        /* @phpstan-ignore-next-line */
        self::$counters[$fiber] = new self();
    }

    /**
     * Fibers are pooled and reused by the event loop, so the counter is dropped once the test is done.
     *
     * @return int<0, max>
     */
    public static function stop(): int
    {
        $fiber = \Fiber::getCurrent();
        $counter = self::current();

        if (null === $fiber || null === $counter) {
            return 0;
        }

        $counter->suspended();
        unset(self::$counters[$fiber]);

        // Assert::resetCount() in a test would send it below zero.
        return max(0, $counter->credited);
    }

    public static function current(): ?self
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber || null === self::$counters) {
            return null;
        }

        return self::$counters[$fiber] ?? null;
    }

    public function suspended(): void
    {
        $this->credited += Assert::getCount() - $this->since;
    }

    public function resumed(): void
    {
        $this->since = Assert::getCount();
    }
}
