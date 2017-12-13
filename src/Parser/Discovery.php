<?php

namespace Asynit\Parser;

use Asynit\Test;
use Asynit\TestCase;
use Symfony\Component\Finder\Finder;

class Discovery
{
    public function discover(string $path): array
    {
        if (\is_file($path)) {
            return $this->doDiscover([$path]);
        }

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in($path)
        ;

        return $this->doDiscover($finder);
    }

    protected function doDiscover($fileIterator): array
    {
        $methods = [];

        foreach ($fileIterator as $file) {
            $existingClasses = get_declared_classes();
            $path = $file;

            if ($path instanceof \SplFileInfo) {
                $path = $path->getRealPath();
            }

            require_once $path;

            $newClasses = array_diff(get_declared_classes(), $existingClasses);

            foreach ($newClasses as $class) {
                if (is_subclass_of($class, TestCase::class)) {
                    foreach (get_class_methods($class) as $method) {
                        if (preg_match('/^test(.+)$/', $method)) {
                            $test = new Test(new \ReflectionMethod($class, $method));
                            $methods[$test->getIdentifier()] = $test;
                        }
                    }
                }
            }
        }

        return $methods;
    }
}
