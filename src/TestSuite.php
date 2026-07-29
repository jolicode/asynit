<?php

namespace Asynit;

/**
 * @internal
 *
 * @template T of \PHPUnit\Framework\TestCase
 */
final class TestSuite
{
    /** @var array<string, Test> */
    public array $tests = [];

    public ?float $startTime = null;

    public ?float $endTime = null;

    /**
     * @param \ReflectionClass<T> $reflectionClass
     */
    public function __construct(
        public readonly \ReflectionClass $reflectionClass,
    ) {
    }

    public function start(): void
    {
        $this->startTime ??= microtime(true);
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
            if (Test::STATE_FAILURE === $test->state && $test->failureIsAssertion) {
                ++$failure;
            }
        }

        return $failure;
    }

    public function getErrors(): int
    {
        $errors = 0;
        foreach ($this->tests as $test) {
            if (Test::STATE_FAILURE === $test->state && !$test->failureIsAssertion) {
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
        if (null === $this->startTime || null === $this->endTime) {
            return 0.0;
        }

        return $this->endTime - $this->startTime;
    }
}
