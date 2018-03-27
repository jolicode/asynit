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

                if (false === strpos($dependency, '::')) {
                    $dependency = $test->getMethod()->getDeclaringClass()->getName().'::'.$dependency;
                }

                $dependentTest = false;

                if (array_key_exists($dependency, $tests)) {
                    $dependentTest = $tests[$dependency];
                } elseif (is_callable($dependency)) {
                    if (false === strpos($dependency, '::')) {
                        $dependentTest = new Test(new \ReflectionMethod($test->getMethod()->getDeclaringClass()->getName(), $annotation->getDependency()), null, false);
                    } else {
                        $parts = explode('::', $dependency);
                        $dependentTest = new Test(new \ReflectionMethod($parts[0], $parts[1]), null, false);
                    }
                    $tests[$dependentTest->getIdentifier()] = $dependentTest;
                }

                if (!$dependentTest) {
                    throw new \RuntimeException(sprintf(
                        'Failed to build test pool "%s" dependency is not resolvable for "%s::%s".',
                        $annotation->getDependency(),
                        $test->getMethod()->getDeclaringClass()->getName(),
                        $test->getMethod()->getName()
                    ));
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
