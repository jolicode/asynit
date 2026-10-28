<?php

namespace Asynit\Runner;

use PHPUnit\Event\DeferringDispatcher;
use PHPUnit\Event\DirectDispatcher;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\Subscriber;
use PHPUnit\Logging\JUnit\JunitXmlLogger;
use PHPUnit\Logging\OpenTestReporting\OtrXmlLogger;
use PHPUnit\Logging\TeamCity\TeamCityLogger;
use PHPUnit\Logging\TestDox\TestResultCollector;
use PHPUnit\Runner\ErrorHandler;
use PHPUnit\Runner\ResultCache\ResultCacheHandler;
use PHPUnit\TestRunner\TestResult\Collector;
use PHPUnit\TextUI\Output\Default\ProgressPrinter\ProgressPrinter;

/**
 * The per test state PHPUnit keeps in a single slot for the whole process, on its singletons and on the objects
 * behind its printers and loggers. FiberSwitchSuspension saves it when a fiber suspends and hands it back when
 * the fiber resumes, so each test sees its own, and ConcurrentRunner resets it when a test starts, as PHPUnit
 * finds it between two tests. Shared by interleaved tests, it gives:
 *
 *  - ErrorHandler::$backupErrorHandlers, the error handler snapshot runBare() takes when a test starts and
 *    restores when it ends: the last test to end finds none and dies on a TypeError, after it has already been
 *    reported as passed;
 *  - Collector::$prepared, whether the running test got past its preparation, which decides if an error or a
 *    skip counts as a test run of its own: the test is counted twice, or not at all;
 *  - the test case the JUnit, Open Test Reporting and TeamCity loggers are writing: a failure lands on the test
 *    that started last, and the test that failed is missing from the report;
 *  - the status the progress printer and the TestDox collector hold until the test finishes: a test is reported
 *    with the outcome of another one that ended meanwhile;
 *  - when the test started, which every duration is measured from.
 *
 * @internal
 */
final class SingletonState
{
    private const array PROPERTIES = [
        Collector::class => ['prepared'],
        ErrorHandler::class => ['backupErrorHandlers'],
        JunitXmlLogger::class => ['currentTestCase', 'preparationFailed', 'prepared', 'time', 'unexpectedOutput'],
        OtrXmlLogger::class => [
            'alreadyFinished',
            'pendingAssertionError',
            'pendingComparisonFailure',
            'pendingReason',
            'pendingStatus',
            'pendingThrowable',
            'testId',
            'testStartSystemCpuTime',
            'testStartTime',
            'testStartTotalCpuTime',
            'testStartUserCpuTime',
            'testWasPrepared',
        ],
        ProgressPrinter::class => ['prepared', 'status'],
        ResultCacheHandler::class => ['time'],
        TeamCityLogger::class => ['preparationFailed', 'prepared', 'testStartedEmitted', 'time'],
        TestResultCollector::class => ['prepared', 'status', 'throwable'],
    ];

    /** @var ?list<array{object, \ReflectionProperty}> */
    private static ?array $properties = null;

    /**
     * @return list<mixed>
     */
    public static function save(): array
    {
        return array_map(static fn (array $property): mixed => $property[1]->getValue($property[0]), self::properties());
    }

    /**
     * @param list<mixed> $state
     */
    public static function restore(array $state): void
    {
        foreach (self::properties() as $i => [$object, $property]) {
            $property->setValue($object, $state[$i]);
        }
    }

    public static function reset(): void
    {
        foreach (self::properties() as [$object, $property]) {
            $property->setValue($object, $property->getDefaultValue());
        }
    }

    /**
     * Resolved on first use, once PHPUnit has created its printers and loggers and sealed the event facade.
     *
     * @return list<array{object, \ReflectionProperty}>
     */
    private static function properties(): array
    {
        if (null !== self::$properties) {
            return self::$properties;
        }

        self::$properties = [];

        foreach (self::holders() as $holder) {
            foreach (self::PROPERTIES[$holder::class] as $name) {
                self::$properties[] = [$holder, new \ReflectionProperty($holder, $name)];
            }
        }

        return self::$properties;
    }

    /**
     * PHPUnit keeps no reference to its printers and loggers, which only exist when asked for (--log-junit, …):
     * they are reached through the subscribers they registered.
     *
     * @return array<int, object>
     */
    private static function holders(): array
    {
        $holders = [spl_object_id(ErrorHandler::instance()) => ErrorHandler::instance()];

        $deferringDispatcher = new \ReflectionProperty(EventFacade::class, 'deferringDispatcher')->getValue(EventFacade::instance());
        \assert($deferringDispatcher instanceof DeferringDispatcher);

        $dispatcher = new \ReflectionProperty(DeferringDispatcher::class, 'dispatcher')->getValue($deferringDispatcher);
        \assert($dispatcher instanceof DirectDispatcher);

        /** @var array<class-string, list<Subscriber>> $subscribersByEvent */
        $subscribersByEvent = new \ReflectionProperty(DirectDispatcher::class, 'subscribers')->getValue($dispatcher);

        foreach ($subscribersByEvent as $subscribers) {
            foreach ($subscribers as $subscriber) {
                // Mangled, so the private property a subscriber inherits from its base class is listed too.
                foreach (get_mangled_object_vars($subscriber) as $value) {
                    if (\is_object($value) && isset(self::PROPERTIES[$value::class])) {
                        $holders[spl_object_id($value)] = $value;
                    }
                }
            }
        }

        return $holders;
    }
}
