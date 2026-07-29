<?php

namespace Asynit\PHPUnit;

use Asynit\Runner\TestStorage;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

/**
 * PHPUnit reports what went wrong through events rather than by handing the throwable back to the caller.
 *
 * The events are emitted from inside the test's own fiber, so the running test is looked up in the fiber local
 * storage rather than matched on the event's test id: that keeps the attribution correct no matter how many
 * tests are in flight.
 *
 * The event facade dispatches a subscriber on a single event type, so the two outcomes need one class each.
 *
 * @internal
 */
final class ResultCollector
{
    public function register(): void
    {
        EventFacade::instance()->registerSubscribers(
            new class implements FailedSubscriber {
                public function notify(Failed $event): void
                {
                    ResultCollector::record($event->throwable(), true);
                }
            },
            new class implements ErroredSubscriber {
                public function notify(Errored $event): void
                {
                    ResultCollector::record($event->throwable(), false);
                }
            },
        );
    }

    public static function record(\PHPUnit\Event\Code\Throwable $throwable, bool $isAssertion): void
    {
        $test = TestStorage::get();

        if (null === $test) {
            return;
        }

        $test->failure = $throwable;
        $test->failureIsAssertion = $isAssertion;
    }
}
