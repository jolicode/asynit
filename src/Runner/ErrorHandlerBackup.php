<?php

namespace Asynit\Runner;

use PHPUnit\Runner\ErrorHandler;

/**
 * TestCase::runBare() snapshots the error handler stack into the ErrorHandler singleton when a test starts
 * and restores it when the test ends, in a single slot. Two interleaved tests overwrite each other's snapshot,
 * and the second one to end finds none and dies on a TypeError, after it has already been reported as passed.
 * FiberSwitchSuspension hands each fiber its own slot back when it resumes.
 *
 * @internal
 */
final class ErrorHandlerBackup
{
    private static ?\ReflectionProperty $property = null;

    /**
     * @return ?list<callable>
     */
    public static function get(): ?array
    {
        /* @phpstan-ignore return.type */
        return self::property()->getValue(ErrorHandler::instance());
    }

    /**
     * @param ?list<callable> $handlers
     */
    public static function set(?array $handlers): void
    {
        self::property()->setValue(ErrorHandler::instance(), $handlers);
    }

    private static function property(): \ReflectionProperty
    {
        return self::$property ??= new \ReflectionProperty(ErrorHandler::class, 'backupErrorHandlers');
    }
}
