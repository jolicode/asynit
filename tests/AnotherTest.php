<?php

namespace Asynit\Tests;

use Asynit\TestCase;

class AnotherTest extends TestCase
{
    public function test_from_another_file()
    {
        return __METHOD__;
    }

    public function get_d()
    {
        return 'd';
    }
}
