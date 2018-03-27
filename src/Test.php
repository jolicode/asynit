<?php

namespace Asynit;

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

    private $displayName;

    private $isRealTest;

    public function __construct(\ReflectionMethod $reflectionMethod, $identifier = null, $isRealTest = true)
    {
        $this->method = $reflectionMethod;
        $this->identifier = $identifier ?: sprintf(
            '%s::%s',
            $this->method->getDeclaringClass()->getName(),
            $this->method->getName()
        );
        $this->displayName = $this->identifier;
        $this->state = self::STATE_PENDING;
        $this->isRealTest = $isRealTest;
    }

    public function isRealTest(): bool
    {
        return $this->isRealTest;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state)
    {
        $this->state = $state;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName)
    {
        $this->displayName = $displayName;
    }
}
