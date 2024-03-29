<?php

namespace Asynit\Parser;

use Asynit\Attribute\Depend;
use Asynit\Attribute\DisplayName;
use Asynit\Pool;
use Asynit\Test;

/**
 * Build test.
 */
final class TestPoolBuilder
{
    /**
     * Build the initial test pool.
     *
     * @param Test[] $tests
     *
     * @throws \RuntimeException
     */
    public function build(array $tests): Pool
    {
        $pool = new Pool();

        $tests = new \ArrayObject($tests);

        foreach ($tests as $test) {
            $this->processTestAnnotations($tests, $test);
            $pool->addTest($test);
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
            $dependency = $attribute->newInstance()->dependency;

            if (isset($tests[$dependency])) {
                $dependentTest = $tests[$dependency];

                $dependentTest->addChildren($test);
                $test->addParent($dependentTest);
                continue;
            }

            if (false === strpos($dependency, '::')) {
                $class = $test->getMethod()->getDeclaringClass()->getName();
                $method = $dependency;
            } else {
                [$class, $method] = explode('::', $dependency, 2);
            }

            if (!method_exists($class, $method)) {
                throw new \RuntimeException(sprintf('Failed to build test pool "%s" dependency is not resolvable for "%s::%s".', $dependency, $test->getMethod()->getDeclaringClass()->getName(), $test->getMethod()->getName()));
            }

            $dependentTest = new Test(new \ReflectionMethod($class, $method), null, false);

            if (isset($tests[$dependentTest->getIdentifier()])) {
                $dependentTest = $tests[$dependentTest->getIdentifier()];
            } else {
                $tests[$dependentTest->getIdentifier()] = $dependentTest;
            }

            $dependentTest->addChildren($test);
            $test->addParent($dependentTest);
        }

        $displayName = $testMethod->getAttributes(DisplayName::class);

        if (\count($displayName) > 0) {
            $test->setDisplayName($displayName[0]->newInstance()->name);
        }
    }
}
