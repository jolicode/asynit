<?php

namespace Asynit\Parser;

use Asynit\Annotation\Depend;
use Asynit\Annotation\DisplayName;
use Asynit\Pool;
use Asynit\Test;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Build test.
 */
class TestPoolBuilder
{
    private $reader;

    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

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

    private function processTestAnnotations(\ArrayObject $tests, Test $test)
    {
        $annotations = $this->reader->getMethodAnnotations($test->getMethod());

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Depend) {
                $dependency = $annotation->getDependency();

                if ($tests->offsetExists($dependency)) {
                    $dependentTest = $tests->offsetGet($dependency);
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
                if ($tests->offsetExists($dependentTest->getIdentifier())) {
                    $dependentTest = $tests->offsetGet($dependentTest->getIdentifier());
                } else {
                    $tests[$dependentTest->getIdentifier()] = $dependentTest;
                }

                $dependentTest->addChildren($test);
                $test->addParent($dependentTest);
            }

            if ($annotation instanceof DisplayName) {
                $test->setDisplayName($annotation->getName());
            }
        }
    }
}
