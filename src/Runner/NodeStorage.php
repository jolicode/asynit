<?php

namespace Asynit\Runner;

/**
 * Fiber local pointer to the node a fiber is running, so anything happening inside a test (the output
 * handler, an event subscriber) can find out which test it belongs to while other tests are in flight.
 *
 * @internal
 */
final class NodeStorage
{
    /** @var \WeakMap<\Fiber<mixed, mixed, mixed, mixed>, TestNode>|null */
    private static ?\WeakMap $localStorage = null;

    public static function set(TestNode $node): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return;
        }

        /* @phpstan-ignore-next-line */
        self::$localStorage ??= new \WeakMap();
        /* @phpstan-ignore-next-line */
        self::$localStorage[$fiber] = $node;
    }

    /**
     * Fibers are pooled and reused by the event loop, so the mapping has to be dropped once a test is done to
     * avoid attributing anything the recycled fiber does afterwards to that test.
     */
    public static function clear(): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber || null === self::$localStorage) {
            return;
        }

        unset(self::$localStorage[$fiber]);
    }

    public static function get(): ?TestNode
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber || null === self::$localStorage) {
            return null;
        }

        return self::$localStorage[$fiber] ?? null;
    }
}
