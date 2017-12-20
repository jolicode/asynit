<?php

namespace Asynit\Parser;

use Asynit\Test;
use Asynit\TestCase;
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
        foreach ($files as $file) {
            $existingClasses = get_declared_classes();
            $path = $file;

            if ($path instanceof \SplFileInfo) {
                $path = $path->getRealPath();
            }

            require_once $path;

            $newClasses = array_diff(get_declared_classes(), $existingClasses);

            foreach ($newClasses as $class) {
                if (!is_subclass_of($class, TestCase::class)) {
                    continue;
                }

                foreach (get_class_methods($class) as $method) {
                    if (!preg_match('/^test(.+)$/', $method)) {
                        continue;
                    }

                    $test = new Test(new \ReflectionMethod($class, $method));
                    $methods[$test->getIdentifier()] = $test;
                }
            }
        }

        return $methods;
    }
}
