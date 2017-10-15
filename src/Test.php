<?php

namespace Asynit;

use Asynit\Runner\FutureHttp;
use Asynit\Runner\FutureHttpPool;
use Http\Client\HttpAsyncClient;

/**
 * A test.
 */
class Test
{
    const STATE_PENDING = 'pending';
    const STATE_RUNNING = 'running';
    const STATE_SUCCESS = 'success';
    const STATE_FAILURE = 'failure';
    const STATE_SKIPPED = 'skipped';

    /** @var Test[] */
    private $parents = [];

    /** @var Test[] */
    private $children = [];

    /** @var array */
    private $arguments = [];

    /** @var \ReflectionMethod */
    private $method;

    private $assertions = [];

    private $identifier;

    private $state;

    public function __construct(\ReflectionMethod $reflectionMethod, $identifier = null)
    {
        $this->method = $reflectionMethod;
        $this->identifier = $identifier ?: sprintf(
            '%s::%s',
            $this->method->getDeclaringClass()->getName(),
            $this->method->getName()
        );
        $this->state = self::STATE_PENDING;
    }

    public function isCompleted(): bool
    {
        return in_array($this->state, [self::STATE_SUCCESS, self::STATE_FAILURE, self::STATE_SKIPPED], true);
    }

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    public function canBeRun(): bool
    {
        if ($this->isCompleted() || $this->isRunning()) {
            return false;
        }

        foreach ($this->getParents() as $test) {
            if (!$test->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state)
    {
        $this->state = $state;
    }

    /**
     * Return an unique identifier for this test.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return \ReflectionMethod
     */
    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    public function addChildren(Test $test)
    {
        $this->children[] = $test;
    }

    public function addParent(Test $test)
    {
        $this->parents[] = $test;
    }

    public function addArgument($argument, Test $test)
    {
        $this->arguments[$test->getIdentifier()] = $argument;
    }

    public function addAssertion($assertion)
    {
        $this->assertions[] = $assertion;
    }

    /**
     * @return array
     */
    public function getAssertions(): array
    {
        return $this->assertions;
    }

    /**
     * @return Test[]
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @return Test[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        $args = [];
        $arguments = $this->arguments;

        foreach ($this->getParents() as $parent) {
            if (array_key_exists($parent->getIdentifier(), $arguments)) {
                $args[] = $arguments[$parent->getIdentifier()];
                unset($arguments[$parent->getIdentifier()]);
            }
        }

        return array_merge($args, array_values($arguments));
    }
}
