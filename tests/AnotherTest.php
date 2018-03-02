<?php

namespace Asynit\Tests;

use Asynit\Annotation\Depend;
use Asynit\TestCase;
use Http\Client\Exception;
use Psr\Http\Message\ResponseInterface;

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
