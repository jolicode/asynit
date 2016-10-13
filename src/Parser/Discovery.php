<?php

namespace Asynit\Parser;

use Asynit\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * Discover test class
 */
class Discovery
{
    /**
     * Return a list of test method
     *
     * @param $directory
     *
     * @return \ReflectionMethod[]
     */
    public function discover($directory)
    {
        $methods = [];
        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in($directory)
        ;

        foreach ($finder as $file) {
            $existingClasses = get_declared_classes();

            include $file->getRealPath();

            $newClasses = array_diff(get_declared_classes(), $existingClasses);

            foreach ($newClasses as $class) {
                if (is_subclass_of($class, TestCase::class)) {
                    foreach (get_class_methods($class) as $method) {
                        if (preg_match('/^test(.+)$/', $method)) {
                            $methods[] = new \ReflectionMethod($class, $method);
                        }
                    }
                }
            }
        }

        return $methods;
    }
}
