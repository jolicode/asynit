<?php

namespace Asynit\Runner;

use PHPUnit\Runner\ErrorHandler;
use PHPUnit\TestRunner\TestResult\Collector;
use PHPUnit\TestRunner\TestResult\Facade as TestResultFacade;

/**
 * The per test state PHPUnit keeps on its singletons, one slot for the whole process. FiberSwitchSuspension
 * saves it when a fiber suspends and hands it back when the fiber resumes, so each test sees its own:
 *
 *  - ErrorHandler::$backupErrorHandlers, the error handler snapshot runBare() takes when a test starts and
 *    restores when it ends. Interleaved tests overwrite each other's, and the last one to end finds none and
 *    dies on a TypeError, after it has already been reported as passed;
 *  - Collector::$prepared, whether the running test got past its preparation, which decides if an error or a
 *    skip counts as a test run of its own. Another test finishing in between resets it, and the test is
 *    counted twice.
 *
 * @internal
 */
final class SingletonState
{
    /** @var array<string, \ReflectionProperty> */
    private static array $properties = [];

    /**
     * @return array{errorHandlers: mixed, prepared: mixed}
     */
    public static function save(): array
    {
        return [
            'errorHandlers' => self::property(ErrorHandler::class, 'backupErrorHandlers')->getValue(ErrorHandler::instance()),
            'prepared' => self::property(Collector::class, 'prepared')->getValue(self::collector()),
        ];
    }

    /**
     * @param array{errorHandlers: mixed, prepared: mixed} $state
     */
    public static function restore(array $state): void
    {
        self::property(ErrorHandler::class, 'backupErrorHandlers')->setValue(ErrorHandler::instance(), $state['errorHandlers']);
        self::property(Collector::class, 'prepared')->setValue(self::collector(), $state['prepared']);
    }

    private static function collector(): Collector
    {
        $collector = self::property(TestResultFacade::class, 'collector')->getValue();
        \assert($collector instanceof Collector);

        return $collector;
    }

    /**
     * @param class-string $class
     */
    private static function property(string $class, string $name): \ReflectionProperty
    {
        return self::$properties[$class.'::'.$name] ??= new \ReflectionProperty($class, $name);
    }
}
