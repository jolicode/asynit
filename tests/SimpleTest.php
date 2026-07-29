<?php

namespace Asynit\Tests;

use Asynit\Attribute\Depend;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;

class SimpleTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function i_want_to_test_something()
    {
    }

    public function depend_but_not_a_test()
    {
        return 'foo';
    }

    #[Depend('depend_but_not_a_test')]
    public function depend_of_depend_but_not_a_test($value)
    {
        return $value.'_bar';
    }

    #[Depend('depend_of_depend_but_not_a_test')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function i_want_to_test_depend($value)
    {
        if ('foo_bar' !== $value) {
            throw new \Exception('Should not throw');
        }
    }

    public function not_a_test()
    {
        throw new \Exception('foo');
    }
}
