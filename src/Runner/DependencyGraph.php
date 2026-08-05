<?php

namespace Asynit\Runner;

use Asynit\Attribute\Depend;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

/**
 * Builds the node graph asynit schedules from, out of the #[Depend] attributes on the tests PHPUnit collected.
 *
 * Unlike PHPUnit's own #[Depends], a dependency may point at any method of any class, whether or not it is a
 * test itself: a producer that is not part of the run gets its own node so it runs once, before its dependents,
 * without ever being reported.
 *
 * @internal
 */
final class DependencyGraph
{
    /**
     * PHPUnit's dependency attributes, mapped to whether they name another class and how the produced value
     * is handed over.
     *
     * @var array<class-string, array{bool, TestNode::CLONE_*}>
     */
    private const PHPUNIT_DEPENDS = [
        Attributes\Depends::class => [false, TestNode::CLONE_NONE],
        Attributes\DependsUsingDeepClone::class => [false, TestNode::CLONE_DEEP],
        Attributes\DependsUsingShallowClone::class => [false, TestNode::CLONE_SHALLOW],
        Attributes\DependsExternal::class => [true, TestNode::CLONE_NONE],
        Attributes\DependsExternalUsingDeepClone::class => [true, TestNode::CLONE_DEEP],
        Attributes\DependsExternalUsingShallowClone::class => [true, TestNode::CLONE_SHALLOW],
    ];

    /** @var array<string, TestNode> */
    private array $nodes = [];

    /**
     * @param TestCase[] $tests
     *
     * @throws \RuntimeException
     */
    public static function build(array $tests): self
    {
        $graph = new self();

        foreach ($tests as $test) {
            $graph->nodes[self::identify($test::class, $test->name())] = new TestNode($test, true);
        }

        // Resolving may add producer nodes, so the list is walked by key rather than with a foreach over the
        // array being modified.
        $queue = array_keys($graph->nodes);

        while ([] !== $queue) {
            $node = $graph->nodes[array_shift($queue)];

            foreach (self::dependenciesOf($node) as [$dependency, $skipIfFailed, $clone]) {
                $identifier = $graph->resolve($node, $dependency);

                if (!isset($graph->nodes[$identifier])) {
                    $graph->nodes[$identifier] = $graph->createProducer($identifier);
                    $queue[] = $identifier;
                }

                $node->addParent($graph->nodes[$identifier], $skipIfFailed, $clone);
            }
        }

        $graph->assertNoCycle();

        return $graph;
    }

    /** @return TestNode[] */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }

    public function isEmpty(): bool
    {
        foreach ($this->nodes as $node) {
            if (!$node->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function next(): ?TestNode
    {
        foreach ($this->nodes as $node) {
            if ($node->canBeRun()) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Both dependency dialects are read: asynit's own #[Depend], and PHPUnit's #[Depends] family.
     *
     * PHPUnit resolves its own at run time against the tests that already passed, which under concurrency
     * means a dependent can start before its producer finished and be skipped for no good reason. Feeding them
     * through the same graph makes them an ordering constraint instead of a race.
     *
     * @return list<array{string, bool, TestNode::CLONE_*}>
     */
    private static function dependenciesOf(TestNode $node): array
    {
        $method = $node->reflectionMethod();
        $dependencies = [];

        foreach ($method->getAttributes(Depend::class) as $attribute) {
            /** @var Depend $depend */
            $depend = $attribute->newInstance();

            $dependencies[] = [$depend->dependency, $depend->skipIfFailed, TestNode::CLONE_NONE];
        }

        foreach (self::PHPUNIT_DEPENDS as $attributeClass => [$isExternal, $clone]) {
            foreach ($method->getAttributes($attributeClass) as $attribute) {
                // Read off the attribute arguments rather than the instance: only the External variants carry
                // a class name, so there is no common accessor to call.
                $arguments = array_values(array_map(
                    static fn (mixed $argument): string => \is_string($argument) ? $argument : '',
                    $attribute->getArguments(),
                ));
                $dependency = $isExternal
                    ? self::identify($arguments[0], $arguments[1] ?? '')
                    : $arguments[0];

                // A failed PHPUnit dependency always skips the dependent, there is no opting out of it.
                $dependencies[] = [$dependency, true, $clone];
            }
        }

        return $dependencies;
    }

    private function resolve(TestNode $node, string $dependency): string
    {
        if (!str_contains($dependency, '::')) {
            // Looked up on the test case class rather than on the class declaring the method, so a test
            // inherited from a base class depends on its own class' method.
            return self::identify($node->test()::class, $dependency);
        }

        [$class, $method] = explode('::', $dependency, 2);

        return self::identify($class, $method);
    }

    private function createProducer(string $identifier): TestNode
    {
        [$class, $method] = explode('::', $identifier, 2);

        if (!class_exists($class) || !method_exists($class, $method)) {
            throw new \RuntimeException(sprintf('Dependency "%s" is not resolvable.', $identifier));
        }

        if (!is_subclass_of($class, TestCase::class)) {
            throw new \RuntimeException(sprintf('Dependency "%s" is declared on "%s", which does not extend "%s".', $identifier, $class, TestCase::class));
        }

        if ((new \ReflectionClass($class))->isAbstract()) {
            throw new \RuntimeException(sprintf('Dependency "%s" is declared on abstract class "%s", which cannot be instantiated.', $identifier, $class));
        }

        return new TestNode(new $class($method), false);
    }

    /**
     * @throws \RuntimeException
     */
    private function assertNoCycle(): void
    {
        $resolved = [];

        foreach ($this->nodes as $identifier => $node) {
            $this->walk($identifier, $node, $resolved, []);
        }
    }

    /**
     * @param array<string, bool> $resolved
     * @param string[]            $path
     */
    private function walk(string $identifier, TestNode $node, array &$resolved, array $path): void
    {
        if (isset($resolved[$identifier])) {
            return;
        }

        $position = array_search($identifier, $path, true);

        if (false !== $position) {
            $cycle = \array_slice($path, (int) $position);
            $cycle[] = $identifier;

            throw new \RuntimeException(sprintf('Circular dependency detected between tests: %s.', implode(' -> ', $cycle)));
        }

        $path[] = $identifier;

        foreach ($node->parents() as $parent) {
            $this->walk(self::identify($parent->test()::class, $parent->test()->name()), $parent, $resolved, $path);
        }

        $resolved[$identifier] = true;
    }

    public static function identify(string $class, string $method): string
    {
        return sprintf('%s::%s', $class, $method);
    }
}
