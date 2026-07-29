<?php

namespace Asynit\Runner;

use PHPUnit\Framework\TestCase;

/**
 * A test in the dependency graph, together with the little scheduling state asynit needs on top of what
 * PHPUnit already tracks on the test case itself.
 *
 * @internal
 */
final class TestNode
{
    /** @var array<array{node: TestNode, skipIfFailed: bool}> */
    private array $children = [];

    /** @var TestNode[] */
    private array $parents = [];

    /** @var array<string, mixed> values produced by the parents, keyed by producer */
    private array $arguments = [];

    private bool $scheduled = false;

    private bool $completed = false;

    /**
     * @param bool $isReported whether PHPUnit collected this test, as opposed to asynit adding it only because
     *                         something depends on it
     */
    public function __construct(
        private readonly TestCase $test,
        private readonly bool $isReported,
    ) {
    }

    public function test(): TestCase
    {
        return $this->test;
    }

    public function isReported(): bool
    {
        return $this->isReported;
    }

    public function reflectionMethod(): \ReflectionMethod
    {
        return new \ReflectionMethod($this->test, $this->test->name());
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function canBeRun(): bool
    {
        if ($this->scheduled || $this->completed) {
            return false;
        }

        foreach ($this->parents as $parent) {
            if (!$parent->completed) {
                return false;
            }
        }

        return true;
    }

    public function markAsScheduled(): void
    {
        $this->scheduled = true;
    }

    public function complete(bool $passed, mixed $result): void
    {
        $this->completed = true;

        if (!$passed) {
            return;
        }

        foreach ($this->children as $child) {
            $child['node']->arguments[DependencyGraph::identify($this->test::class, $this->test->name())] = $result;
        }
    }

    /**
     * Completes the node without running it, and cascades to whatever depended on it.
     *
     * @return TestNode[] the nodes that were skipped, this one included
     */
    public function skip(): array
    {
        if ($this->completed) {
            return [];
        }

        $this->completed = true;
        $skipped = [$this];

        foreach ($this->children as $child) {
            $skipped = array_merge($skipped, $child['node']->skip());
        }

        return $skipped;
    }

    /** @return TestNode[] the children that asked to be skipped when this node fails */
    public function childrenToSkipOnFailure(): array
    {
        $children = [];

        foreach ($this->children as $child) {
            if ($child['skipIfFailed']) {
                $children[] = $child['node'];
            }
        }

        return $children;
    }

    public function addParent(self $parent, bool $skipIfFailed): void
    {
        $this->parents[] = $parent;
        $parent->children[] = ['node' => $this, 'skipIfFailed' => $skipIfFailed];
    }

    /** @return TestNode[] */
    public function parents(): array
    {
        return $this->parents;
    }

    /**
     * Values produced by the parents, in parent declaration order. PHPUnit passes array_values() of this to
     * the test method, so the keys only identify the producer.
     *
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        $arguments = [];

        foreach ($this->parents as $parent) {
            $identifier = DependencyGraph::identify($parent->test()::class, $parent->test()->name());

            if (array_key_exists($identifier, $this->arguments)) {
                $arguments[$identifier] = $this->arguments[$identifier];
            }
        }

        return $arguments;
    }
}
