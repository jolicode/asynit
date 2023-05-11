<?php

namespace Asynit\Tests;

use Asynit\Annotation\Depend;
use Asynit\Annotation\Test;
use Asynit\Annotation\TestCase;

#[TestCase]
class Simple
{
    #[Test]
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

    #[Test]
    #[Depend('depend_of_depend_but_not_a_test')]
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
