<?php

namespace Asynit;

use Asynit\Assert\AssertCaseTrait;

abstract class TestCase
{
    private $test;

    use AssertCaseTrait;

    public function __construct(Test $test)
    {
        $this->test = $test;
    }

    public function setUp()
    {
    }
}
