<?php

namespace Asynit\Tests;

use Asynit\Annotation\Depend;
use Asynit\TestCase;
use Http\Client\Exception;
use Psr\Http\Message\ResponseInterface;

class FunctionalTests extends TestCase
{
    public function testReturn()
    {
        return 'tata';
    }

    public function testGet()
    {
        $response = yield $this->get('http://127.0.0.1:8081');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStatusCode(200, $response);

        return 'foo';
    }

    public function testException()
    {
        $exception = null;

        try {
            $response = yield $this->get('http://something-is-not-reachable');
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'Not null exception');
        $this->assertInstanceOf(Exception::class, $exception);
    }

    /**
     * @Depend("testGet")
     */
    public function testDepend($value)
    {
        $this->assertEquals('foo', $value);
    }

    /** @Depend("Asynit\Tests\AnotherTest::test_from_another_file") */
    public function testDependFromAnotherFile($value)
    {
        $this->assertSame('Asynit\Tests\AnotherTest::test_from_another_file', $value);
    }

    public function testStartParallel()
    {
        return time();
    }

    public function testParallel1()
    {
        yield $this->get('http://127.0.0.1:8081/delay/7');
    }

    public function testParallel2()
    {
        yield $this->get('http://127.0.0.1:8081/delay/7');
    }

    public function testParallel3()
    {
        yield $this->get('http://127.0.0.1:8081/delay/7');
    }

    public function testParallel4()
    {
        yield $this->get('http://127.0.0.1:8081/delay/7');
    }

    /**
     * @Depend("testStartParallel")
     * @Depend("testParallel1")
     * @Depend("testParallel2")
     * @Depend("testParallel3")
     * @Depend("testParallel4")
     */
    public function testEndParallel($start)
    {
        $end = time();

        $this->assertLessThan(10, $end - $start);
    }

    public function get_a()
    {
        return 'a';
    }

    /** @Depend("get_a") */
    public function get_b($a)
    {
        $this->assertSame('a', $a);

        return 'b';
    }

    /**
     * @Depend("get_a")
     * @Depend("get_b")
     */
    public function test_c($a, $b)
    {
        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
    }

    /**
     * @Depend("get_a")
     * @Depend("get_b")
     * @Depend("Asynit\Tests\AnotherTest::get_d")
     */
    public function test_c_with_d($a, $b, $d)
    {
        $this->assertSame('a', $a);
        $this->assertSame('b', $b);
        $this->assertSame('d', $d);
    }
}
