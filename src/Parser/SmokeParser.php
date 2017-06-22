<?php

declare(strict_types=1);

namespace Asynit\Parser;

use Asynit\SmokerTestCase;
use Asynit\Test;
use Symfony\Component\Yaml\Yaml;

class SmokeParser
{
    public function parse($file)
    {
        $methods = [];
        $contents = file_get_contents($file);
        $data = Yaml::parse($contents);

        foreach ($data as $url => $expected) {
            $test = new Test(new \ReflectionMethod(SmokerTestCase::class, 'smokeTest'), $url);
            $argument = [$url, $expected];
            $test->addArgumentWithoutRef($argument, $test);

            $methods[$url] = $test;
        }

        return $methods;
    }
}
