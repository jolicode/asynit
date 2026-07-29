<?php

namespace Asynit\Runner;

use Asynit\Test;

/** @internal */
final class TestStorage
{
    /** @var \WeakMap<\Fiber<void, void, void, void>, Test|null>|null */
    private static ?\WeakMap $localStorage = null;

    public static function set(Test $test): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return;
        }

        /* @phpstan-ignore-next-line */
        self::$localStorage ??= new \WeakMap();
        /* @phpstan-ignore-next-line */
        self::$localStorage[$fiber] = $test;
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

    public static function get(): ?Test
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return null;
        }

        /* @phpstan-ignore-next-line */
        self::$localStorage ??= new \WeakMap();

        /* @phpstan-ignore-next-line */
        return self::$localStorage[$fiber] ?? null;
    }
}
