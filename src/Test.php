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
    private array $parents = [];

    /** @var Test[] */
    private array $children = [];

    /** @var mixed[] */
    private array $arguments = [];

    /** @var \ReflectionMethod */
    private \ReflectionMethod $method;

    /** @var string[] */
    private array $assertions = [];

    private string $identifier;

    private string $state;

    private string $displayName;

    private bool $isRealTest;

    public function __construct(\ReflectionMethod $reflectionMethod, string $identifier = null, bool $isRealTest = true)
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

    public function setState(string $state): void
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

    public function addChildren(Test $test): void
    {
        $this->children[] = $test;
    }

    public function addParent(Test $test): void
    {
        $this->parents[] = $test;
    }

    public function addArgument(mixed $argument, Test $test): void
    {
        $this->arguments[$test->getIdentifier()] = $argument;
    }

    public function addAssertion(string $assertion): void
    {
        $this->assertions[] = $assertion;
    }

    /** @return string[] */
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

    /** @return mixed[] */
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

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }
}
