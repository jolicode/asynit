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
