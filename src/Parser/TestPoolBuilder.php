<?php

namespace Asynit\Parser;

use Asynit\Annotation\Depend;
use Asynit\Annotation\DisplayName;
use Asynit\Test;
use Asynit\Pool;
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
     *
     * @return Pool
     */
    public function build($tests)
    {
        $pool = new Pool();

        foreach ($tests as $test) {
            /** @var Depend[]|null $depends */
            $annotations = $this->reader->getMethodAnnotations($test->getMethod());

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Depend) {
                    $dependency = $annotation->getDependency();

                    if (!preg_match('/::/', $dependency)) {
                        $dependency = $test->getMethod()->getDeclaringClass()->getName().'::'.$dependency;
                    }

                    if (!array_key_exists($dependency, $tests)) {
                        throw new \RuntimeException(sprintf(
                            'Failed to build test pool "%s" dependency is not resolvable for "%s::%s".',
                            $annotation->getDependency(),
                            $test->getMethod()->getDeclaringClass()->getName(),
                            $test->getMethod()->getName()
                        ));
                    }

                    $dependentTest = $tests[$dependency];
                    $dependentTest->addChildren($test);
                    $test->addParent($dependentTest);
                }

                if ($annotation instanceof DisplayName) {
                    $test->setDisplayName($annotation->getName());
                }
            }

            $pool->addTest($test);
        }

        return $pool;
    }
}
