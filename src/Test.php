<?php

namespace Asynit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * @internal
 */
final class Test
{
    public const STATE_PENDING = 'pending';
    public const STATE_RUNNING = 'running';
    public const STATE_SUCCESS = 'success';
    public const STATE_FAILURE = 'failure';
    public const STATE_SKIPPED = 'skipped';

    /** @var Test[] */
    private array $parents = [];

    /** @var array<array{ test: Test, skipIfFailed: bool }> */
    private array $children = [];

    /** @var array<string, mixed> */
    private array $arguments = [];

    private string $identifier;

    private string $displayName;

    /**
     * Whether the test has been handed over to the runner. A scheduled test may not have started yet, as it
     * still has to acquire a slot on the concurrency semaphore, but it must not be picked up a second time.
     */
    private bool $scheduled = false;

    public string $state;

    public ?float $startTime = null;

    public ?float $endTime = null;

    public string $output = '';

    public ?\PHPUnit\Event\Code\Throwable $failure = null;

    /** Whether {@see $failure} is an assertion failure rather than an unexpected error. */
    public bool $failureIsAssertion = false;

    public int $assertionCount = 0;

    /**
     * @param TestSuite<PHPUnitTestCase>|null   $suite         the suite this test is reported in, or null for a test that is
     *                                                         only run because something else depends on it
     * @param \ReflectionClass<PHPUnitTestCase> $testCaseClass the class to instantiate to run this test, which is not
     *                                                         necessarily the class declaring the method (inheritance)
     */
    public function __construct(
        public readonly ?TestSuite $suite,
        public readonly \ReflectionClass $testCaseClass,
        public readonly \ReflectionMethod $method,
        public readonly bool $isRealTest = true,
    ) {
        $this->identifier = sprintf('%s::%s', $testCaseClass->getName(), $method->getName());
        $this->displayName = $this->identifier;
        $this->state = self::STATE_PENDING;
    }

    public function isCompleted(): bool
    {
        return in_array($this->state, [self::STATE_SUCCESS, self::STATE_FAILURE, self::STATE_SKIPPED], true);
    }

    public function isRunning(): bool
    {
        return self::STATE_RUNNING === $this->state;
    }

    public function isPending(): bool
    {
        return self::STATE_PENDING === $this->state;
    }

    public function canBeRun(): bool
    {
        if ($this->scheduled || $this->isCompleted() || $this->isRunning()) {
            return false;
        }

        foreach ($this->getParents() as $test) {
            if (!$test->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function markAsScheduled(): void
    {
        $this->scheduled = true;
    }

    public function start(): void
    {
        $this->suite?->start();
        $this->startTime = microtime(true);
        $this->state = self::STATE_RUNNING;
    }

    public function success(): void
    {
        $this->endTime = microtime(true);
        $this->state = self::STATE_SUCCESS;
        $this->suite?->tryEnd();
    }

    public function failure(?\PHPUnit\Event\Code\Throwable $error, bool $isAssertion = false): void
    {
        $this->endTime = microtime(true);
        $this->state = self::STATE_FAILURE;
        $this->failure = $error;
        $this->failureIsAssertion = $isAssertion;
        $this->suite?->tryEnd();
    }

    public function skipped(): void
    {
        $this->suite?->start();
        $this->startTime = microtime(true);
        $this->endTime = $this->startTime;
        $this->state = self::STATE_SKIPPED;
        $this->suite?->tryEnd();
    }

    /**
     * Output written by the test itself, captured per test even when tests run concurrently.
     */
    public function appendOutput(string $output): void
    {
        $this->output .= $output;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    public function addChildren(Test $test, bool $skipIfFailed): void
    {
        $this->children[] = [
            'test' => $test,
            'skipIfFailed' => $skipIfFailed,
        ];
    }

    public function addParent(Test $test): void
    {
        $this->parents[] = $test;
    }

    public function addArgument(mixed $argument, Test $test): void
    {
        $this->arguments[$test->getIdentifier()] = $argument;
    }

    public function getAssertionsCount(): int
    {
        return $this->assertionCount;
    }

    /**
     * @return Test[]
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @return iterable<Test>
     */
    public function getChildren(bool $onlySkipIfFailed = false): iterable
    {
        foreach ($this->children as $child) {
            if ($onlySkipIfFailed && !$child['skipIfFailed']) {
                continue;
            }

            yield $child['test'];
        }
    }

    /**
     * Values produced by the parents, in parent declaration order. PHPUnit takes them keyed by dependency and
     * passes array_values() of that to the test method, so the keys are only there to identify the producer.
     *
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        $args = [];
        $arguments = $this->arguments;

        foreach ($this->getParents() as $parent) {
            $identifier = $parent->getIdentifier();

            if (array_key_exists($identifier, $arguments)) {
                $args[$identifier] = $arguments[$identifier];
                unset($arguments[$identifier]);
            }
        }

        return array_merge($args, $arguments);
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getTime(): float
    {
        if (null === $this->startTime || null === $this->endTime) {
            return 0.0;
        }

        return $this->endTime - $this->startTime;
    }
}
