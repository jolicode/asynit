<?php

namespace Asynit\Parser;

use Asynit\Attribute\Depend;
use Asynit\Attribute\DisplayName;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestSuite;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Build test.
 */
final class TestPoolBuilder
{
    /**
     * Build the initial test pool.
     *
     * @param TestSuite<PHPUnitTestCase>[] $testSuites
     *
     * @throws \RuntimeException
     */
    public function build(array $testSuites): Pool
    {
        $pool = new Pool();

        /** @var \ArrayObject<string, Test> $tests */
        $tests = new \ArrayObject();

        foreach ($testSuites as $testSuite) {
            foreach ($testSuite->tests as $test) {
                $tests[$test->getIdentifier()] = $test;
            }
        }

        // processTestAnnotations may register new tests for dependencies that are not part of any suite; the
        // ArrayObject iterator picks them up so that they get their own dependencies resolved too.
        foreach ($tests as $test) {
            $this->processTestAnnotations($tests, $test);
            $pool->tests[] = $test;
        }

        $this->assertNoCircularDependency($pool);

        return $pool;
    }

    /**
     * @param \ArrayObject<string, Test> $tests
     */
    private function processTestAnnotations(\ArrayObject $tests, Test $test): void
    {
        $testMethod = $test->getMethod();
        $attributes = $testMethod->getAttributes(Depend::class);

        foreach ($attributes as $attribute) {
            /** @var Depend $dependency */
            $dependency = $attribute->newInstance();

            $dependentTest = $this->resolveDependency($tests, $test, $dependency->dependency);

            $dependentTest->addChildren($test, $dependency->skipIfFailed);
            $test->addParent($dependentTest);
        }

        $displayName = $testMethod->getAttributes(DisplayName::class);

        if (\count($displayName) > 0) {
            $test->setDisplayName($displayName[0]->newInstance()->name);
        }
    }

    /**
     * @param \ArrayObject<string, Test> $tests
     *
     * @throws \RuntimeException
     */
    private function resolveDependency(\ArrayObject $tests, Test $test, string $dependency): Test
    {
        if (false === strpos($dependency, '::')) {
            // A dependency without a class is looked up on the test case class, not on the class declaring the
            // method, so that a test inherited from a base class depends on its own class' method.
            $class = $test->testCaseClass->getName();
            $method = $dependency;
        } else {
            [$class, $method] = explode('::', $dependency, 2);
        }

        if (!class_exists($class) || !method_exists($class, $method)) {
            throw new \RuntimeException(sprintf('Failed to build test pool "%s" dependency is not resolvable for "%s".', $dependency, $test->getIdentifier()));
        }

        $identifier = sprintf('%s::%s', $class, $method);

        if (isset($tests[$identifier])) {
            return $tests[$identifier];
        }

        if (!is_subclass_of($class, PHPUnitTestCase::class)) {
            throw new \RuntimeException(sprintf('Failed to build test pool "%s" dependency of "%s" is declared on "%s", which does not extend "%s".', $dependency, $test->getIdentifier(), $class, PHPUnitTestCase::class));
        }

        $reflectionClass = new \ReflectionClass($class);

        if ($reflectionClass->isAbstract()) {
            throw new \RuntimeException(sprintf('Failed to build test pool "%s" dependency of "%s" is declared on abstract class "%s", which cannot be instantiated.', $dependency, $test->getIdentifier(), $class));
        }

        $dependentTest = new Test(null, $reflectionClass, $reflectionClass->getMethod($method), false);
        $tests[$identifier] = $dependentTest;

        return $dependentTest;
    }

    /**
     * @throws \RuntimeException
     */
    private function assertNoCircularDependency(Pool $pool): void
    {
        /** @var array<string, bool> $resolved true once the whole subtree of a test is known to be acyclic */
        $resolved = [];

        foreach ($pool->tests as $test) {
            $this->walkParents($test, $resolved, []);
        }
    }

    /**
     * @param array<string, bool> $resolved
     * @param string[]            $path
     *
     * @throws \RuntimeException
     */
    private function walkParents(Test $test, array &$resolved, array $path): void
    {
        $identifier = $test->getIdentifier();

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

        foreach ($test->getParents() as $parent) {
            $this->walkParents($parent, $resolved, $path);
        }

        $resolved[$identifier] = true;
    }
}
