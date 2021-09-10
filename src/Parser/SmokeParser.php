<?php

declare(strict_types=1);

namespace Asynit\Parser;

use Asynit\SmokerTestCase;
use Asynit\SmokeTest;
use Symfony\Component\Yaml\Yaml;

class SmokeParser
{
    public function parse($file, $host)
    {
        $methods = [];
        $contents = file_get_contents($file);
        $data = Yaml::parse($contents);

        foreach ($data as $url => $configuration) {
            $url = $host . $url;
            $test = new SmokeTest(new \ReflectionMethod(SmokerTestCase::class, 'smokeTest'), $url);
            $argument = [$url, $configuration, $test];
            $test->addArgument($argument, $test);

            $methods[$url] = $test;
        }

        return $methods;
    }
}
