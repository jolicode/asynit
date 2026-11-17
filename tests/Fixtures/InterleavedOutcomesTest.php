<?php

namespace Asynit\Tests\Fixtures;

use function Amp\delay;

/**
 * Run by ReportTest with a concurrency of 2, not by the suite: the first test fails while the second is still
 * running, and the third waits for the slot the first frees before erroring in its setUp().
 */
class InterleavedOutcomesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if ('test_errors_in_set_up_once_a_slot_frees' === $this->name()) {
            throw new \RuntimeException('Expected error.');
        }
    }

    public function test_fails_while_another_test_runs()
    {
        delay(0.1);
        $this->fail('Expected failure.');
    }

    public function test_passes_after_another_test_failed()
    {
        delay(0.3);
        $this->assertTrue(true);
    }

    public function test_errors_in_set_up_once_a_slot_frees()
    {
        $this->assertTrue(true);
    }
}
