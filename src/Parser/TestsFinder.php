<?php

namespace Asynit\Parser;

use Asynit\Annotation\Test as TestAnnotation;
use Asynit\Annotation\TestCase;
use Asynit\Test;
use Symfony\Component\Finder\Finder;

class TestsFinder
{
    public function findTests(string $path): array
    {
        if (\is_file($path)) {
            return $this->doFindTests([$path]);
        }

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($path)
        ;

        return $this->doFindTests($finder);
    }

    private function doFindTests($files): array
    {
        $methods = [];

        foreach ($files as $file) {
            $existingClasses = get_declared_classes();
            $path = $file;

            if ($path instanceof \SplFileInfo) {
                $path = $path->getRealPath();
            }

            require_once $path;

            $newClasses = array_diff(get_declared_classes(), $existingClasses);

            foreach ($newClasses as $class) {
                $reflectionClass = new \ReflectionClass($class);
                $testCases = $reflectionClass->getAttributes(TestCase::class);

                if (0 === count($testCases)) {
                    continue;
                }

                foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                    $tests = $reflectionMethod->getAttributes(TestAnnotation::class);
                    $test = null;

                    if (count($tests) > 0) {
                        $test = new Test($reflectionMethod);
                    } elseif (preg_match('/^test(.+)$/', $reflectionMethod->getName())) {
                        $test = new Test($reflectionMethod);
                    }

                    if (null !== $test) {
                        $methods[$test->getIdentifier()] = $test;
                    }
                }
            }
        }

        return $methods;
    }
}
