<?php

namespace Asynit\Tests;

use Asynit\Assert\AssertCaseTrait;
use Asynit\Attribute\Depend;
use Asynit\Attribute\TestCase;

use function Amp\delay;

#[TestCase]
class FunctionalTests
{
    use AssertCaseTrait;

//    public function testReturn()
//    {
//        return 'tata';
//    }
//
//    #[Depend("Asynit\Tests\AnotherTest::test_from_another_file")]
//    public function testDependFromAnotherFile($value)
//    {
//        $this->assertSame('Asynit\Tests\AnotherTest::test_from_another_file', $value);
//    }
//
//    public function testStartParallel()
//    {
//        return time();
//    }
//
//    public function testParallel1()
//    {
//        delay(4);
//    }
//
//    public function testParallel2()
//    {
//        delay(5);
//    }
//
//    public function testParallel3()
//    {
//        delay(6);
//    }
//
//    public function testParallel4()
//    {
//        delay(7);
//    }
//
//    #[Depend('testStartParallel')]
//    #[Depend('testParallel1')]
//    #[Depend('testParallel2')]
//    #[Depend('testParallel3')]
//    #[Depend('testParallel4')]
//    public function testEndParallel($start)
//    {
//        $end = time();
//
//        $this->assertLessThan(10, $end - $start);
//    }
//
//    public function get_a()
//    {
//        return 'a';
//    }
//
//    #[Depend('get_a')]
//    public function get_b($a)
//    {
//        $this->assertSame('a', $a);
//
//        return 'b';
//    }
//
//    #[Depend('get_a')]
//    #[Depend('get_b')]
//    public function test_c($a, $b)
//    {
//        $this->assertSame('a', $a);
//        $this->assertSame('b', $b);
//    }
//
//    #[Depend('get_a')]
//    #[Depend('get_b')]
//    #[Depend("Asynit\Tests\AnotherTest::get_d")]
//    public function test_c_with_d($a, $b, $d)
//    {
//        $this->assertSame('a', $a);
//        $this->assertSame('b', $b);
//        $this->assertSame('d', $d);
//    }
}
