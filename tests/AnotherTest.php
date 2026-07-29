<?php

namespace Asynit\Tests;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class AnotherTest extends \PHPUnit\Framework\TestCase
{
    #[DoesNotPerformAssertions]
    public function test_from_another_file()
    {
        return __METHOD__;
    }

    public function get_d()
    {
        return 'd';
    }
}
