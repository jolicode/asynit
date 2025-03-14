<?php

namespace Asynit;

use bovigo\assert\AssertionFailure; /**
 * @internal
 *
 * @template T of object
 */
final class TestSuite
{
    /** @var array<string, Test> */
    public array $tests = [];

    public float $startTime;

    public float $endTime;

    /**
     * @param \ReflectionClass<T> $reflectionClass
     */
    public function __construct(
        public readonly \ReflectionClass $reflectionClass,
    ) {
    }

    public function start(): void
    {
        if (isset($this->startTime)) {
            return;
        }

        $this->startTime = microtime(true);
    }

    public function tryEnd(): void
    {
        foreach ($this->tests as $test) {
            if (!$test->isCompleted()) {
                return;
            }
        }

        $this->endTime = microtime(true);
    }

    public function getFailure(): int
    {
        $failure = 0;
        foreach ($this->tests as $test) {
            if (Test::STATE_FAILURE === $test->state && $test->failure instanceof AssertionFailure) {
                ++$failure;
            }
        }

        return $failure;
    }

    public function getErrors(): int
    {
        $errors = 0;
        foreach ($this->tests as $test) {
            if (Test::STATE_FAILURE === $test->state && !$test->failure instanceof AssertionFailure) {
                ++$errors;
            }
        }

        return $errors;
    }

    public function getSuccess(): int
    {
        $success = 0;
        foreach ($this->tests as $test) {
            if (Test::STATE_SUCCESS === $test->state) {
                ++$success;
            }
        }

        return $success;
    }

    public function getSkipped(): int
    {
        $skipped = 0;
        foreach ($this->tests as $test) {
            if (Test::STATE_SKIPPED === $test->state) {
                ++$skipped;
            }
        }

        return $skipped;
    }

    public function getAssertions(): int
    {
        $assertions = 0;
        foreach ($this->tests as $test) {
            $assertions += $test->getAssertionsCount();
        }

        return $assertions;
    }

    public function getTime(): float
    {
        if (!isset($this->endTime)) {
            return 0.0;
        }

        return $this->endTime - $this->startTime;
    }
}
