<?php

namespace Asynit\Tests;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

use function Amp\delay;

/**
 * The tests interleave on purpose: were one credited with the assertions the others make while it waits,
 * the one without any would be reported risky.
 */
class AssertionCountTest extends \PHPUnit\Framework\TestCase
{
    public function test_asserts_while_others_wait()
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->assertTrue(true);
            delay(0.01);
        }
    }

    #[DoesNotPerformAssertions]
    public function test_does_not_assert_while_others_do()
    {
        delay(0.05);
    }
}
