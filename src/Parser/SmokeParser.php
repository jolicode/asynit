<?php

declare(strict_types=1);

namespace Asynit\Parser;

use Asynit\SmokerTestCase;
use Asynit\Test;
use Symfony\Component\Yaml\Yaml;

class SmokeParser
{
    private $host;

    public function __construct(string $host = '')
    {
        $this->host = $host;
    }

    public function parse($file)
    {
        $methods = [];
        $contents = file_get_contents($file);
        $data = Yaml::parse($contents);
        $baseDir = \dirname($file);

        foreach ($data as $name => $expected) {
            $url = $expected['url'] ?? $name;
            $fullUrl = $this->host . $url;
            $test = new Test(new \ReflectionMethod(SmokerTestCase::class, 'smokeTest'), $name);
            $argument = [$fullUrl, $expected, $baseDir, $name];
            $test->addArgument($argument, $test);

            $methods[$url] = $test;
        }

        return $methods;
    }
}
