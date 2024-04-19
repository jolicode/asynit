<?php

namespace Asynit\Parser;

use Asynit\Attribute\Depend;
use Asynit\Attribute\DisplayName;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestSuite;

/**
 * Build test.
 */
final class TestPoolBuilder
{
    /**
     * Build the initial test pool.
     *
     * @param TestSuite<object>[] $testSuites
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

        foreach ($tests as $test) {
            $this->processTestAnnotations($tests, $test);
            $pool->tests[] = $test;
        }

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

            if (isset($tests[$dependency->dependency])) {
                $dependentTest = $tests[$dependency->dependency];

                $dependentTest->addChildren($test, $dependency->skipIfFailed);
                $test->addParent($dependentTest);
                continue;
            }

            if (false === strpos($dependency->dependency, '::')) {
                $class = $test->getMethod()->getDeclaringClass()->getName();
                $method = $dependency->dependency;
            } else {
                [$class, $method] = explode('::', $dependency->dependency, 2);
            }

            if (!method_exists($class, $method)) {
                throw new \RuntimeException(sprintf('Failed to build test pool "%s" dependency is not resolvable for "%s::%s".', $dependency->dependency, $test->getMethod()->getDeclaringClass()->getName(), $test->getMethod()->getName()));
            }

            $dependentTest = new Test(null, new \ReflectionMethod($class, $method), null, false);

            if (isset($tests[$dependentTest->getIdentifier()])) {
                $dependentTest = $tests[$dependentTest->getIdentifier()];
            } else {
                $tests[$dependentTest->getIdentifier()] = $dependentTest;
            }

            $dependentTest->addChildren($test, $dependency->skipIfFailed);
            $test->addParent($dependentTest);
        }

        $displayName = $testMethod->getAttributes(DisplayName::class);

        if (\count($displayName) > 0) {
            $test->setDisplayName($displayName[0]->newInstance()->name);
        }
    }
}
