<?php

namespace Asynit\Runner;

use Asynit\Test;

/** @internal */
final class TestStorage
{
    private static ?\WeakMap $localStorage = null;

    public static function set(Test $test): void
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return;
        }

        self::$localStorage ??= new \WeakMap();
        self::$localStorage[$fiber] = $test;
    }

    public static function get(): ?Test
    {
        $fiber = \Fiber::getCurrent();

        if (null === $fiber) {
            return null;
        }

        self::$localStorage ??= new \WeakMap();

        return self::$localStorage[$fiber] ?? null;
    }
}
