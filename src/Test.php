<?php

namespace Asynit;

use Asynit\Runner\FutureHttp;
use Asynit\Runner\FutureHttpPool;

/**
 * A test.
 */
class Test
{
    /** @var Test[] */
    private $parents = [];

    /** @var Test[] */
    private $children = [];

    /** @var array */
    private $arguments;

    /** @var \ReflectionMethod */
    private $method;

    /** @var FutureHttpPool */
    private $futureHttpPool;

    public function __construct(\ReflectionMethod $reflectionMethod)
    {
        $this->method = $reflectionMethod;
        $this->arguments = [];
        $this->futureHttpPool = new FutureHttpPool();
    }

    /**
     * Return an unique identifier for this test.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return sprintf(
            '%s::%s',
            $this->method->getDeclaringClass()->getName(),
            $this->method->getName()
        );
    }

    /**
     * @return FutureHttpPool
     */
    public function getFutureHttpPool()
    {
        return $this->futureHttpPool;
    }

    /**
     * @return \ReflectionMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function addChildren(Test $test)
    {
        $this->children[] = $test;
    }

    public function addParent(Test $test)
    {
        $this->parents[] = $test;
    }

    public function addArgument(&$argument, Test $test)
    {
        $this->arguments[$test->getIdentifier()] = &$argument;
    }

    /**
     * @return Test[]
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * @return Test[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return array_values($this->arguments);
    }

    /**
     * @param FutureHttp[] $futureHttps
     * @param Test         $test
     */
    public function mergeFutureHttp($futureHttps, Test $test)
    {
        foreach ($futureHttps as $futureHttp) {
            $futureHttp->setTest($test);
        }

        $this->futureHttpPool->merge($futureHttps);
    }
}
